<?php

namespace Modules\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Http\Requests\UpdateRoomRequest;
use Modules\Room\Http\Requests\DeleteRoomRequest;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomCategory;
use Yajra\DataTables\DataTables;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:room-list|room-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:room-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:room-show', ['only' => ['show']]);
        $this->middleware('permission:room-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:room-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Room::with('pg', 'category')->select('id', 'public_id', 'pg_id', 'category_id', 'room_no', 'bed_capacity', 'rent_amount');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pg_name', function ($row) {
                    return $row->pg?->pg_name ?? '—';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category?->category_name ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'room-show';
                    $edit = true ? 'room-edit' : '';
                    $delete = $flag ? 'room-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $pgList = PgManagement::select('id', 'pg_name')->get();

            return view('room::room.index', compact('pgList'));
        }
    }

    public function categoriesByPg(Request $request)
    {
        $pgId = $request->query('pg_id');
        if (! $pgId) {
            return response()->json([]);
        }

        $categories = RoomCategory::where('pg_id', $pgId)
            ->orderBy('category_name')
            ->get(['id', 'category_name']);

        return response()->json($categories->map(fn ($c) => ['value' => $c->id, 'label' => $c->category_name]));
    }

    public function show($id)
    {
        try {
            $room = Room::byAnyKey($id)->first();
            if (! is_null($room)) {
                return response()->json(['status_code' => 200, 'message' => 'View room', 'result' => $room]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Room not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreRoomRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            Room::create($data);

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
            $room = Room::byAnyKey($id)->first();
            if (! is_null($room)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit room', 'result' => $room]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Room not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateRoomRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $room = Room::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $room->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('room::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteRoomRequest $request, $id)
    {
        try {
            $room = Room::findByAnyKeyOrFail($id);
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $room->update($data);
            $room->delete();

            return response()->json(['status_code' => 200, 'message' => __('room::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
