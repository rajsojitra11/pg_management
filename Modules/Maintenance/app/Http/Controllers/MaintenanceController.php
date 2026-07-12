<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Maintenance\Http\Requests\DeleteMaintenanceRequest;
use Modules\Maintenance\Http\Requests\StoreMaintenanceRequest;
use Modules\Maintenance\Http\Requests\UpdateMaintenanceRequest;
use Modules\Maintenance\Models\Maintenance;
use Modules\PgManagement\Models\PgManagement;
use Yajra\DataTables\DataTables;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:maintenance-list|maintenance-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:maintenance-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:maintenance-show', ['only' => ['show']]);
        $this->middleware('permission:maintenance-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:maintenance-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        if (request()->ajax()) {
            $user = auth()->user();
            $query = Maintenance::with('complaint.pg', 'complaint.room', 'createdBy')
                ->select('id', 'public_id', 'maintenance_no', 'complaint_id', 'cost', 'proof', 'description', 'maintenance_date', 'status', 'created_by');

            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('complaint_no', function ($row) {
                    return $row->complaint?->complaint_no ?? '—';
                })
                ->addColumn('pg_name', function ($row) {
                    return $row->complaint?->pg?->pg_name ?? '—';
                })
                ->addColumn('room_no', function ($row) {
                    return $row->complaint?->room?->room_no ?? '—';
                })
                ->addColumn('created_user', function ($row) {
                    return $row->createdBy?->name ?? '—';
                })
                ->addColumn('proof_url', function ($row) {
                    if ($row->proof) {
                        $url = asset('storage/'.$row->proof);

                        return '<a href="'.$url.'" target="_blank" class="text-zinc-900 underline hover:text-zinc-600"><i class="fa-solid fa-file mr-1"></i>View</a>';
                    }

                    return '—';
                })
                ->addColumn('action', function ($row) {
                    $flag = true;
                    $show = 'maintenance-show';
                    $edit = $flag ? 'maintenance-edit' : '';
                    $delete = $flag ? 'maintenance-delete' : '';
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

            return view('maintenance::index', compact('pgList'));
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Maintenance::with('complaint.pg', 'complaint.room', 'complaint.category', 'complaint.service', 'createdBy')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->first();
            if (! is_null($maintenance)) {
                return response()->json(['status_code' => 200, 'message' => 'View maintenance', 'result' => $maintenance]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Maintenance not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function store(StoreMaintenanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['maintenance_no'] = $this->generateMaintenanceNo();
            $data['created_by'] = auth()->id();

            if ($request->hasFile('proof')) {
                $data['proof'] = $request->file('proof')->store('maintenance/proof', 'public');
            }

            $maintenance = Maintenance::create($data);

            $complaintStatus = match ($data['status'] ?? 'pending') {
                'in_progress' => 'in_progress',
                'completed' => 'resolved',
                default => null,
            };
            if ($complaintStatus && $maintenance->complaint) {
                $maintenance->complaint->update([
                    'status' => $complaintStatus,
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('maintenance::message.created')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $query = Maintenance::with('complaint.pg', 'complaint.room', 'createdBy')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->first();
            if (! is_null($maintenance)) {
                return response()->json(['status_code' => 200, 'message' => 'Edit maintenance', 'result' => $maintenance]);
            } else {
                return response()->json(['status_code' => 404, 'message' => 'Maintenance not found.']);
            }
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function update(UpdateMaintenanceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $query = Maintenance::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->firstOrFail();
            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('proof')) {
                if ($maintenance->proof) {
                    Storage::disk('public')->delete($maintenance->proof);
                }
                $data['proof'] = $request->file('proof')->store('maintenance/proof', 'public');
            }

            $maintenance->update($data);

            $complaintStatus = match ($data['status'] ?? 'pending') {
                'in_progress' => 'in_progress',
                'completed' => 'resolved',
                default => null,
            };
            if ($complaintStatus && $maintenance->fresh()->complaint) {
                $maintenance->fresh()->complaint->update([
                    'status' => $complaintStatus,
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json(['status_code' => 200, 'message' => __('maintenance::message.updated')]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function destroy(DeleteMaintenanceRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $query = Maintenance::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->firstOrFail();
            $data = $request->validated();
            $data['deleted_by'] = auth()->id();

            if ($maintenance->proof) {
                Storage::disk('public')->delete($maintenance->proof);
            }

            $maintenance->update($data);
            $maintenance->delete();

            return response()->json(['status_code' => 200, 'message' => __('maintenance::message.deleted')]);
        } catch (Exception $e) {
            return response()->json(['status_code' => 500, 'message' => 'Something went wrong. Please try again.']);
        }
    }

    public function nextMaintenanceNo()
    {
        return response()->json(['maintenance_no' => $this->generateMaintenanceNo()]);
    }

    private function generateMaintenanceNo(): string
    {
        $year = now()->format('Y');
        $prefix = 'MNT'.$year;

        $last = Maintenance::where('maintenance_no', 'like', $prefix.'%')
            ->orderByDesc('maintenance_no')
            ->value('maintenance_no');

        if ($last) {
            $seq = (int) substr($last, 8) + 1;
        } else {
            $seq = 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        abort(404);
    }
}
