<?php

namespace Modules\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Http\Requests\DeleteRoomCategoryRequest;
use Modules\Room\Http\Requests\StoreRoomCategoryRequest;
use Modules\Room\Http\Requests\UpdateRoomCategoryRequest;
use Modules\Room\Models\RoomCategory;
use Yajra\DataTables\DataTables;

class RoomCategoryController extends Controller
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
        if (request()->ajax()) {
            $user = auth()->user();
            $query = RoomCategory::with('pg')->select('id', 'public_id', 'pg_id', 'category_name', 'status');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'room-category-show';
                    $edit = true ? 'room-category-edit' : '';
                    $delete = $flag ? 'room-category-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
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

            return view('room::category.index', compact('pgList'));
        }
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
                return response()->json(['status_code' => 200, 'message' => 'View category', 'result' => $category]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Category not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreRoomCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            RoomCategory::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('room::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = RoomCategory::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $category = $query->first();
            if (! is_null($category)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit category', 'result' => $category]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Category not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
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

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('room::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteRoomCategoryRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = RoomCategory::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $category = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $category->update($data);
            $category->delete();

            return response()->json(['status_code' => 200, 'message' => __('room::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
