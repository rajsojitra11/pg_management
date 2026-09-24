<?php

namespace Modules\FoodMenu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Modules\FoodMenu\Http\Requests\StoreFoodMenuItemRequest;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;

class FoodMenuItemApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile-foodmenu-edit', ['only' => ['store', 'destroy']]);
    }

    public function store(StoreFoodMenuItemRequest $request)
    {
        try {
            $user = auth()->user();
            $menu = $this->findFoodMenu((int) $request->input('food_menu_id'), $user);
            if (is_null($menu)) {
                return response()->json(['message' => 'Food menu not found.'], 404);
            }

            $data = $request->validated();
            $data['food_menu_id'] = $menu->id;
            $data['sort_order'] = $data['sort_order'] ?? ((int) FoodMenuItem::where('food_menu_id', $menu->id)->max('sort_order')) + 1;
            $data['status'] = $data['status'] ?? 'active';
            $data['created_by'] = auth()->id();

            $item = FoodMenuItem::create($data);

            return response()->json(['data' => $this->format($item)], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function destroy($item)
    {
        try {
            $user = auth()->user();
            $query = FoodMenuItem::byAnyKey($item)
                ->whereHas('foodMenu', fn ($q) => $q->whereNull('deleted_at'));
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('foodMenu.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $model = $query->firstOrFail();

            $model->update(['deleted_by' => auth()->id()]);
            $model->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    private function findFoodMenu(int $foodMenuId, $user): ?FoodMenu
    {
        $query = FoodMenu::byAnyKey($foodMenuId)->whereNull('deleted_at');
        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        return $query->first();
    }

    private function format(FoodMenuItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'public_id' => $item->public_id,
            'food_menu_id' => (string) $item->food_menu_id,
            'day' => $item->day,
            'meal_time' => $item->meal_time,
            'item_name' => $item->item_name,
            'description' => $item->description,
            'sort_order' => $item->sort_order,
            'status' => $item->status,
        ];
    }
}
