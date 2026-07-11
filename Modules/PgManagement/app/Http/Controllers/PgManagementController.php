<?php

namespace Modules\PgManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\PgManagement\Http\Requests\DeletePgManagementRequest;
use Modules\PgManagement\Http\Requests\StorePgManagementRequest;
use Modules\PgManagement\Http\Requests\UpdatePgManagementRequest;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;
use Yajra\DataTables\DataTables;

class PgManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pgmanagement-list|pgmanagement-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:pgmanagement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:pgmanagement-show', ['only' => ['show']]);
        $this->middleware('permission:pgmanagement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:pgmanagement-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = PgManagement::with('owner')->select('id', 'public_id', 'pg_name', 'owner_id', 'mobile_no', 'total_block', 'total_room', 'country_id', 'state_id', 'city_id', 'pincode', 'address', 'status');

            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }

            if ($search = trim((string) request('filter_search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('pg_name', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('owner', function ($row) {
                    return $row->owner?->email ?? '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'pgmanagement-show';
                    $edit = true ? 'pgmanagement-edit' : '';
                    $delete = $flag ? 'pgmanagement-delete' : '';
                    $showURL = '';
                    $editURL = '';

                    return view('layouts-tw.action', compact('row', 'show', 'edit', 'delete', 'showURL', 'editURL'));
                })
                ->escapeColumns([])
                ->make(true);
        } else {
            $user = auth()->user();

            if ($user->hasRole('Pg_Admin')) {
                $pgAdminUsers = $user->status === 'Active' ? collect([$user]) : collect();
            } else {
                $pgAdminUsers = User::role('Pg_Admin')->where('status', 'Active')->get(['id', 'email', 'name']);
            }

            return view('pgmanagement::index', compact('pgAdminUsers'));
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = PgManagement::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }
            $pgManagement = $query->first();
            if (! is_null($pgManagement)) {
                return response()->json(['status_code' => 200, 'message' => 'View PG', 'result' => $pgManagement]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'PG not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StorePgManagementRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();
            PgManagement::create($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('pgmanagement::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = PgManagement::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }
            $pgManagement = $query->first();
            if (! is_null($pgManagement)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit PG', 'result' => $pgManagement]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'PG not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdatePgManagementRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = PgManagement::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }
            $pgManagement = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();
            $pgManagement->update($data);

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('pgmanagement::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeletePgManagementRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = PgManagement::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->where('owner_id', $user->id);
            }
            $pgManagement = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            $pgManagement->update($data);
            $pgManagement->delete();

            return response()->json(['status_code' => 200, 'message' => __('pgmanagement::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }
}
