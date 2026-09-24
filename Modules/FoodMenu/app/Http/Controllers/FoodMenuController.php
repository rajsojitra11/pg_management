<?php

namespace Modules\FoodMenu\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\FoodMenu\Http\Requests\DeleteFoodMenuRequest;
use Modules\FoodMenu\Http\Requests\StoreFoodMenuRequest;
use Modules\FoodMenu\Http\Requests\UpdateFoodMenuRequest;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;
use Modules\PgManagement\Models\PgManagement;
use Yajra\DataTables\DataTables;

class FoodMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:foodmenu-list|foodmenu-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:foodmenu-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:foodmenu-show', ['only' => ['show']]);
        $this->middleware('permission:foodmenu-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:foodmenu-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = FoodMenu::with('pg', 'user')->select('id', 'public_id', 'user_id', 'pg_id', 'menu_type', 'title', 'week_start_date', 'special_date', 'status', 'created_at');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user?->name ?? '—';
                })
                ->addColumn('menu_date', function ($row) {
                    return $row->menu_type === 'special' ? $row->special_date : $row->week_start_date;
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'foodmenu-show';
                    $edit = true ? 'foodmenu-edit' : '';
                    $delete = $flag ? 'foodmenu-delete' : '';
                    $showURL = '';
                    $editURL = '';
                    $extraBtn = [
                        [
                            'extra' => 'foodmenu-edit',
                            'extraURL' => '',
                            'extraIcon' => 'utensils',
                            'extraToolTip' => 'Items',
                            'extraClass' => 'manage-items',
                            'extraBgColor' => 'secondary',
                        ],
                    ];

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL', 'extraBtn'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $user = auth()->user();
            $query = PgManagement::select('id', 'pg_name')->where('status', 'active');

            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }

            $pgList = $query->get();

            return view('foodmenu::foodmenu.index', compact('pgList'));
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenu::with('pg', 'user', 'items')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $foodMenu = $query->first();
            if (! is_null($foodMenu)) {
                return response()->json(['status_code' => 200, 'message' => 'View food menu', 'result' => $foodMenu]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Food menu not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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

            $foodMenu = FoodMenu::create($data);

            $this->syncItems($foodMenu, $data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('foodmenu::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenu::with('pg', 'user', 'items')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $foodMenu = $query->first();
            if (! is_null($foodMenu)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit food menu', 'result' => $foodMenu]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Food menu not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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
            $foodMenu = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            $foodMenu->update($data);

            $this->syncItems($foodMenu, $data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('foodmenu::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteFoodMenuRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenu::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $foodMenu = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $foodMenu->update($data);
            $foodMenu->delete();

            return response()->json(['status_code' => 200, 'message' => __('foodmenu::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    private const WEEKLY_DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    private const MEAL_TIMES = ['breakfast', 'lunch', 'dinner'];

    private function syncItems(FoodMenu $foodMenu, array $data): void
    {
        if ($foodMenu->menu_type === 'special') {
            if (! array_key_exists('special_items', $data)) {
                return;
            }
            $this->syncItemRow($foodMenu, null, $data['special_items'] ?? []);

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
}
