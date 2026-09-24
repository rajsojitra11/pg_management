<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Complaint\Models\Complaint;
use Modules\Maintenance\Models\Maintenance;
use Modules\Payment\Models\Payment;
use Modules\Tenant\Models\Tenant;

class ReportApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-list');
    }

    /**
     * Counts for the report center cards, scoped like the web report index.
     */
    public function summary()
    {
        $user = auth()->user();

        $tenantQuery = Tenant::query();
        $paymentQuery = Payment::query()->where('verified', 'verified');
        $complaintQuery = Complaint::query();
        $maintenanceQuery = Maintenance::query();

        if ($user->hasRole('Pg_Admin')) {
            $tenantQuery->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $paymentQuery->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $complaintQuery->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $maintenanceQuery->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $tenantQuery->where('pg_id', $pgId);
            $paymentQuery->where('pg_id', $pgId);
            $complaintQuery->where('pg_id', $pgId);
            $maintenanceQuery->whereHas('complaint', fn ($q) => $q->where('pg_id', $pgId));
        }

        return response()->json([
            'data' => [
                'tenant' => $tenantQuery->count(),
                'payment' => $paymentQuery->count(),
                'complaint' => $complaintQuery->count(),
                'maintenance' => $maintenanceQuery->count(),
            ],
        ]);
    }

    /**
     * Tenant report rows with filters, paginated.
     */
    public function tenants()
    {
        $query = $this->tenantReportQuery();

        $rows = $query->orderByDesc('checkin_date')->paginate((int) request('per_page', 10));

        return response()->json([
            'data' => $rows->map(fn ($t) => [
                'id' => (string) $t->id,
                'public_id' => $t->public_id,
                'name' => $t->name,
                'email' => $t->email,
                'phone' => $t->phone,
                'room_no' => $t->room?->room_no ?? '—',
                'checkin_date' => $t->checkin_date?->toDateString(),
                'expected_checkout_date' => $t->expected_checkout_date?->toDateString(),
                'monthly_rent' => $t->monthly_rent !== null ? (float) $t->monthly_rent : null,
                'status' => $t->status,
            ]),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    /**
     * Payment report rows — approved payments grouped by room + tenant, paginated.
     */
    public function payments()
    {
        $query = $this->paymentReportQuery();

        $rows = $query->orderByDesc('total_amount')->paginate((int) request('per_page', 10));

        return response()->json([
            'data' => $rows->map(function ($row) {
                return [
                    'room_no' => $row->room?->room_no ?? '—',
                    'tenant_name' => $row->tenant?->name ?? '—',
                    'payment_count' => (int) $row->payment_count,
                    'total_amount' => (float) $row->total_amount,
                ];
            }),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    /**
     * Complaint report rows with filters, paginated.
     */
    public function complaints()
    {
        $query = $this->complaintReportQuery();

        $rows = $query->orderByDesc('complaint_date')->paginate((int) request('per_page', 10));

        return response()->json([
            'data' => $rows->map(fn ($c) => [
                'id' => (string) $c->id,
                'public_id' => $c->public_id,
                'complaint_no' => $c->complaint_no,
                'room_no' => $c->room?->room_no ?? '—',
                'category_name' => $c->category?->service_category_name ?? '—',
                'service_name' => $c->service?->service_name ?? '—',
                'complaint_date' => $c->complaint_date?->toDateString(),
                'note' => $c->note,
                'status' => $c->status,
            ]),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    /**
     * Maintenance report rows with filters, paginated.
     */
    public function maintenance()
    {
        $query = $this->maintenanceReportQuery();

        $rows = $query->orderByDesc('maintenance_date')->paginate((int) request('per_page', 10));

        return response()->json([
            'data' => $rows->map(fn ($m) => [
                'id' => (string) $m->id,
                'public_id' => $m->public_id,
                'maintenance_no' => $m->maintenance_no,
                'complaint_no' => $m->complaint?->complaint_no ?? '—',
                'room_no' => $m->complaint?->room?->room_no ?? '—',
                'maintenance_date' => $m->maintenance_date?->toDateString(),
                'description' => $m->description,
                'cost' => $m->cost !== null ? (float) $m->cost : null,
                'status' => $m->status,
            ]),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    /**
     * Base query for the tenant report with scoping + filters applied.
     */
    protected function tenantReportQuery()
    {
        $user = auth()->user();

        $query = Tenant::with('pg', 'room')
            ->select('id', 'public_id', 'name', 'email', 'phone', 'pg_id', 'room_id', 'checkin_date', 'expected_checkout_date', 'monthly_rent', 'status');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($from = request('from')) {
            $query->where('checkin_date', '>=', $from);
        }

        if ($to = request('to')) {
            $query->where('checkin_date', '<=', $to);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($roomId = request('room_id')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    /**
     * Base query for the payment report (approved, grouped) with scoping + filters applied.
     */
    protected function paymentReportQuery()
    {
        $user = auth()->user();

        $query = Payment::query()
            ->with(['pg', 'room', 'tenant'])
            ->where('verified', 'verified')
            ->select(['room_id', 'tenant_id'])
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupBy(['room_id', 'tenant_id']);

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($from = request('from')) {
            $query->where('payment_date', '>=', $from);
        }

        if ($to = request('to')) {
            $query->where('payment_date', '<=', $to);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('tenant', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($sq) => $sq->where('room_no', 'like', "%{$search}%"))
                    ->orWhereHas('pg', fn ($sq) => $sq->where('pg_name', 'like', "%{$search}%"));
            });
        }

        if ($roomId = request('room_id')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    /**
     * Base query for the complaint report with scoping + filters applied.
     */
    protected function complaintReportQuery()
    {
        $user = auth()->user();

        $query = Complaint::with('pg', 'room', 'category', 'service')
            ->select('id', 'public_id', 'complaint_no', 'pg_id', 'room_id', 'service_category_id', 'service_id', 'complaint_date', 'note', 'status');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->where('pg_id', $pgId);
        }

        if ($from = request('from')) {
            $query->where('complaint_date', '>=', $from);
        }

        if ($to = request('to')) {
            $query->where('complaint_date', '<=', $to);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($roomId = request('room_id')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    /**
     * Base query for the maintenance report with scoping + filters applied.
     */
    protected function maintenanceReportQuery()
    {
        $user = auth()->user();

        $query = Maintenance::with('complaint.pg', 'complaint.room')
            ->select('id', 'public_id', 'maintenance_no', 'complaint_id', 'cost', 'description', 'maintenance_date', 'status');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId = request('pg_id')) {
            $query->whereHas('complaint', fn ($q) => $q->where('pg_id', $pgId));
        }

        if ($from = request('from')) {
            $query->where('maintenance_date', '>=', $from);
        }

        if ($to = request('to')) {
            $query->where('maintenance_date', '<=', $to);
        }

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('maintenance_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('complaint', fn ($sq) => $sq->where('complaint_no', 'like', "%{$search}%"));
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($roomId = request('room_id')) {
            $query->whereHas('complaint', fn ($sq) => $sq->where('room_id', $roomId));
        }

        return $query;
    }
}
