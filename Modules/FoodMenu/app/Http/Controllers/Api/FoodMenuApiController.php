<?php

namespace Modules\FoodMenu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\FoodMenu\Http\Requests\StoreFoodMenuRequest;
use Modules\FoodMenu\Http\Requests\UpdateFoodMenuRequest;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;

class FoodMenuApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile-foodmenu-list|mobile-foodmenu-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:mobile-foodmenu-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:mobile-foodmenu-view', ['only' => ['show']]);
        $this->middleware('permission:mobile-foodmenu-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:mobile-foodmenu-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = auth()->user();
        $query = FoodMenu::with('pg', 'user');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($menuType = request('menu_type')) {
            $query->where('menu_type', $menuType);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $menus = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $menus->map(fn ($m) => $this->format($m));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $menus->currentPage(),
                'last_page' => $menus->lastPage(),
                'per_page' => $menus->perPage(),
                'total' => $menus->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenu::with('pg', 'user', 'items')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $m = $query->first();
            if (! is_null($m)) {
                $data = $this->format($m);
                $data['items'] = $m->items->map(fn ($item) => [
                    'id' => (string) $item->id,
                    'public_id' => $item->public_id,
                    'day' => $item->day,
                    'meal_time' => $item->meal_time,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'sort_order' => $item->sort_order,
                    'status' => $item->status,
                ]);

                return response()->json(['data' => $data]);
            }

            return response()->json(['message' => 'Food menu not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreFoodMenuRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['created_by'] = auth()->id();
            $data['status'] ??= 'active';

            $menu = FoodMenu::create($data);
            $this->syncItems($menu, $data);
            $menu->load('pg', 'user');

            DB::commit();

            return response()->json(['data' => $this->format($menu)], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdateFoodMenuRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = FoodMenu::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $menu = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            $menu->update($data);
            $this->syncItems($menu, $data);
            $menu->load('pg', 'user');

            DB::commit();

            return response()->json(['data' => $this->format($menu)]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenu::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $menu = $query->firstOrFail();

            $menu->update(['deleted_by' => auth()->id()]);
            $menu->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    private const WEEKLY_DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    private const MEAL_TIMES = ['breakfast', 'lunch', 'dinner'];

    private function syncItems(FoodMenu $foodMenu, array $data): void
    {
        if ($foodMenu->menu_type === 'special') {
            $specialItems = $data['special_items'] ?? null;
            if (! is_array($specialItems)) {
                return;
            }

            $this->syncItemRow($foodMenu, null, $specialItems);

            return;
        }

        $weekItems = $data['week_items'] ?? null;
        if (! is_array($weekItems)) {
            return;
        }

        foreach (self::WEEKLY_DAYS as $day) {
            $this->syncItemRow($foodMenu, $day, $weekItems[$day] ?? []);
        }
    }

    private function syncItemRow(FoodMenu $foodMenu, ?string $day, array $payload): void
    {
        foreach (self::MEAL_TIMES as $meal) {
            $incoming = $this->normalizeItems($payload[$meal] ?? []);
            $sortOrder = 0;

            $existing = FoodMenuItem::where('food_menu_id', $foodMenu->id)
                ->where('meal_time', $meal)
                ->where(fn ($q) => $day === null ? $q->whereNull('day') : $q->where('day', $day))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $keepIds = [];

            foreach ($incoming as $entry) {
                $sortOrder++;

                $matched = null;
                if ($entry['id'] !== null) {
                    $matched = $existing->firstWhere('id', $entry['id']);
                }

                if ($matched) {
                    $keepIds[] = $matched->id;
                    $matched->update([
                        'item_name' => $entry['item_name'],
                        'description' => $entry['description'],
                        'sort_order' => $sortOrder,
                        'updated_by' => auth()->id(),
                    ]);

                    continue;
                }

                $item = FoodMenuItem::create([
                    'food_menu_id' => $foodMenu->id,
                    'day' => $day,
                    'meal_time' => $meal,
                    'item_name' => $entry['item_name'],
                    'description' => $entry['description'],
                    'sort_order' => $sortOrder,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
                $keepIds[] = $item->id;
            }

            $existing->whereNotIn('id', $keepIds)->each->delete();
        }
    }

    private function normalizeItems(mixed $payload): array
    {
        $raw = is_array($payload) ? $payload : ['item_name' => (string) $payload];
        if (is_array($raw) && ! array_is_list($raw)) {
            $raw = [$raw];
        }

        $items = [];

        foreach ($raw as $entry) {
            if (is_array($entry)) {
                $name = trim((string) ($entry['item_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $items[] = [
                    'item_name' => $name,
                    'description' => array_key_exists('description', $entry)
                        ? trim((string) $entry['description'])
                        : null,
                    'id' => isset($entry['id']) ? (int) $entry['id'] : null,
                ];

                continue;
            }

            $name = trim((string) $entry);
            if ($name === '') {
                continue;
            }
            $items[] = ['item_name' => $name, 'description' => null, 'id' => null];
        }

        return $items;
    }

    private function format(FoodMenu $m): array
    {
        return [
            'id' => (string) $m->id,
            'public_id' => $m->public_id,
            'pg_id' => (string) $m->pg?->id,
            'pg_name' => $m->pg?->pg_name,
            'menu_type' => $m->menu_type,
            'title' => $m->title,
            'week_start_date' => $m->week_start_date,
            'special_date' => $m->special_date,
            'status' => $m->status,
            'created_by' => $m->user?->name,
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }
}
