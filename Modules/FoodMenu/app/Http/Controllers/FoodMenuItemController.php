<?php

namespace Modules\FoodMenu\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Modules\FoodMenu\Http\Requests\DeleteFoodMenuItemRequest;
use Modules\FoodMenu\Http\Requests\StoreFoodMenuItemRequest;
use Modules\FoodMenu\Models\FoodMenu;
use Modules\FoodMenu\Models\FoodMenuItem;
use Yajra\DataTables\DataTables;

class FoodMenuItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:foodmenu-edit', ['only' => ['index', 'store', 'destroy']]);
    }

    public function index()
    {
        if (! request()->ajax()) {
            abort(404);
        }

        try {
            $user = auth()->user();
            $foodMenuId = (int) request('food_menu_id');

            $foodMenu = $this->findFoodMenu($foodMenuId, $user);
            if (is_null($foodMenu)) {
                return response()->json(['status_code' => 404, 'message' => 'Food menu not found.']);
            }

            $query = FoodMenuItem::select('id', 'public_id', 'food_menu_id', 'day', 'meal_time', 'item_name', 'description', 'sort_order', 'status', 'created_at')
                ->where('food_menu_id', $foodMenuId);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<button data-id="'.$row->public_id.'" class="p-1.5 rounded-md text-red-400 hover:text-red-600 hover:bg-red-50 inline-flex items-center delete-item" title="Delete"><i class="fa-solid fa-trash text-xs"></i></button>';
                })
                ->escapeColumns([])
                ->make(true);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreFoodMenuItemRequest $request)
    {
        try {
            $user = auth()->user();
            $foodMenu = $this->findFoodMenu((int) $request->input('food_menu_id'), $user);
            if (is_null($foodMenu)) {
                return response()->json(['status_code' => 404, 'message' => 'Food menu not found.']);
            }

            $data = $request->validated();
            $data['sort_order'] = $data['sort_order'] ?? ((int) FoodMenuItem::where('food_menu_id', $foodMenu->id)->max('sort_order')) + 1;
            $data['created_by'] = auth()->id();

            FoodMenuItem::create($data);

            return response()->json(['status_code' => 200, 'message' => __('foodmenu::message.item_created')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteFoodMenuItemRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = FoodMenuItem::byAnyKey($id)
                ->whereHas('foodMenu', fn ($q) => $q->whereNull('deleted_at'));
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('foodMenu.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $item = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $item->update($data);
            $item->delete();

            return response()->json(['status_code' => 200, 'message' => __('foodmenu::message.item_deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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
}
