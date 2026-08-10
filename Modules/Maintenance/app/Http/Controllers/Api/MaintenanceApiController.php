<?php

namespace Modules\Maintenance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Maintenance\Http\Requests\StoreMaintenanceRequest;
use Modules\Maintenance\Http\Requests\UpdateMaintenanceRequest;
use Modules\Maintenance\Models\Maintenance;

class MaintenanceApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile-maintenance-list|mobile-maintenance-create', ['only' => ['index', 'store']]);
        $this->middleware('permission:mobile-maintenance-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:mobile-maintenance-view', ['only' => ['show']]);
        $this->middleware('permission:mobile-maintenance-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:mobile-maintenance-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = auth()->user();
        $query = Maintenance::with('complaint.pg', 'complaint.room', 'createdBy');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->whereHas('complaint', fn ($q) => $q->where('pg_id', $pgId));
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('maintenance_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('complaint.room', fn ($r) => $r->where('room_no', 'like', "%{$search}%"));
            });
        }

        $maintenances = $query->orderByDesc('created_at')->paginate((int) request('per_page', 10));

        $data = $maintenances->map(fn ($m) => [
            'public_id' => $m->public_id,
            'maintenance_no' => $m->maintenance_no,
            'complaint_id' => (string) $m->complaint?->id,
            'complaint_no' => $m->complaint?->complaint_no,
            'pg_id' => (string) $m->complaint?->pg?->id,
            'pg_name' => $m->complaint?->pg?->pg_name,
            'room_id' => (string) $m->complaint?->room?->id,
            'room_no' => $m->complaint?->room?->room_no,
            'cost' => (float) $m->cost,
            'proof' => $m->proof,
            'description' => $m->description,
            'maintenance_date' => $m->maintenance_date?->toDateString(),
            'status' => $m->status,
            'created_by' => $m->createdBy?->name,
            'created_at' => $m->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $maintenances->currentPage(),
                'last_page' => $maintenances->lastPage(),
                'per_page' => $maintenances->perPage(),
                'total' => $maintenances->total(),
            ],
        ]);
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $query = Maintenance::with('complaint.pg', 'complaint.room', 'createdBy')->byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $m = $query->first();
            if (! is_null($m)) {
                return response()->json([
                    'data' => [
                        'public_id' => $m->public_id,
                        'maintenance_no' => $m->maintenance_no,
                        'complaint_id' => (string) $m->complaint?->id,
                        'complaint_no' => $m->complaint?->complaint_no,
                        'pg_id' => (string) $m->complaint?->pg?->id,
                        'pg_name' => $m->complaint?->pg?->pg_name,
                        'room_id' => (string) $m->complaint?->room?->id,
                        'room_no' => $m->complaint?->room?->room_no,
                        'cost' => (float) $m->cost,
                        'proof' => $m->proof,
                        'description' => $m->description,
                        'maintenance_date' => $m->maintenance_date?->toDateString(),
                        'status' => $m->status,
                        'created_by' => $m->createdBy?->name,
                        'created_at' => $m->created_at?->toIso8601String(),
                    ],
                ]);
            }

            return response()->json(['message' => 'Maintenance not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function store(StoreMaintenanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['maintenance_no'] = $this->generateMaintenanceNo();
            $data['created_by'] = auth()->id();
            $maintenance = Maintenance::create($data);
            $maintenance->load('complaint.pg', 'complaint.room');

            DB::commit();

            return response()->json([
                'data' => [
                    'public_id' => $maintenance->public_id,
                    'maintenance_no' => $maintenance->maintenance_no,
                    'complaint_id' => (string) $maintenance->complaint?->id,
                    'complaint_no' => $maintenance->complaint?->complaint_no,
                    'pg_id' => (string) $maintenance->complaint?->pg?->id,
                    'pg_name' => $maintenance->complaint?->pg?->pg_name,
                    'room_id' => (string) $maintenance->complaint?->room?->id,
                    'room_no' => $maintenance->complaint?->room?->room_no,
                    'cost' => (float) $maintenance->cost,
                    'proof' => $maintenance->proof,
                    'description' => $maintenance->description,
                    'maintenance_date' => $maintenance->maintenance_date?->toDateString(),
                    'status' => $maintenance->status,
                    'created_at' => $maintenance->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
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
            $maintenance->update($data);
            $maintenance->load('complaint.pg', 'complaint.room');

            $complaintStatus = match ($data['status']) {
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

            return response()->json([
                'data' => [
                    'public_id' => $maintenance->public_id,
                    'maintenance_no' => $maintenance->maintenance_no,
                    'complaint_id' => (string) $maintenance->complaint?->id,
                    'complaint_no' => $maintenance->complaint?->complaint_no,
                    'pg_id' => (string) $maintenance->complaint?->pg?->id,
                    'pg_name' => $maintenance->complaint?->pg?->pg_name,
                    'room_id' => (string) $maintenance->complaint?->room?->id,
                    'room_no' => $maintenance->complaint?->room?->room_no,
                    'cost' => (float) $maintenance->cost,
                    'proof' => $maintenance->proof,
                    'description' => $maintenance->description,
                    'maintenance_date' => $maintenance->maintenance_date?->toDateString(),
                    'status' => $maintenance->status,
                    'created_at' => $maintenance->created_at?->toIso8601String(),
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
            $query = Maintenance::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->firstOrFail();
            $maintenance->update(['deleted_by' => auth()->id()]);
            $maintenance->delete();

            return response()->noContent();
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function updateStatus($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            ]);

            $user = auth()->user();
            $query = Maintenance::byAnyKey($id);
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
            }
            $maintenance = $query->firstOrFail();
            $maintenance->update([
                'status' => $validated['status'],
                'updated_by' => auth()->id(),
            ]);

            $complaintStatus = match ($validated['status']) {
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

            return response()->json(['data' => ['status' => $maintenance->status]]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    private function generateMaintenanceNo(): string
    {
        $year = now()->format('Y');
        $prefix = 'MNT'.$year;

        $last = Maintenance::where('maintenance_no', 'like', $prefix.'%')
            ->orderByDesc('maintenance_no')
            ->value('maintenance_no');

        if ($last) {
            $seq = (int) substr($last, 7) + 1;
        } else {
            $seq = 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
