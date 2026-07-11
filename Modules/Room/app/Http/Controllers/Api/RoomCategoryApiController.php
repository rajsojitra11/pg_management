<?php

namespace Modules\Room\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Room\Http\Requests\StoreRoomCategoryRequest;
use Modules\Room\Http\Requests\UpdateRoomCategoryRequest;
use Modules\Room\Models\RoomCategory;

class RoomCategoryApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:room-category-list|room-category-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:room-category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:room-category-show', ['only' => ['show']]);
        $this->middleware('permission:room-category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:room-category-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = auth()->user();
        $query = RoomCategory::with('pg');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($search = trim((string) request('search'))) {
            $query->where('category_name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('category_name')->paginate((int) request('per_page', 10));

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->public_id,
                'categoryName' => $category->category_name,
                'status' => $category->status,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = RoomCategory::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $category = $query->first();
            if (! is_null($category)) {
                return response()->json([
                    'data' => [
                        'id' => $category->public_id,
                        'categoryName' => $category->category_name,
                        'status' => $category->status,
                    ],
                ]);
            }

            return response()->json(['message' => 'Category not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreRoomCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $category = RoomCategory::create($data);
            $category->load('pg');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => $category->public_id,
                    'categoryName' => $category->category_name,
                    'status' => $category->status,
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function update(UpdateRoomCategoryRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = RoomCategory::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $category = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $category->update($data);
            $category->load('pg');

            DB::commit();

            return response()->json([
                'data' => [
                    'id' => $category->public_id,
                    'categoryName' => $category->category_name,
                    'status' => $category->status,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $query = RoomCategory::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $category = $query->firstOrFail();
            $data['deleted_by'] = auth()->id();

            $category->update($data);
            $category->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
