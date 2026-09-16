<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Complaint\Models\Complaint;
use Modules\Maintenance\Models\Maintenance;
use Modules\Payment\Models\Payment;
use Modules\PgManagement\Models\PgManagement;
use Modules\Report\Exports\ComplaintReportExport;
use Modules\Report\Exports\MaintenanceReportExport;
use Modules\Report\Exports\PaymentDetailReportExport;
use Modules\Report\Exports\PaymentReportExport;
use Modules\Report\Exports\TenantReportExport;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-list', ['only' => [
            'index',
            'tenants', 'payments', 'complaints', 'maintenance',
            'tenantsExport', 'paymentsExport', 'complaintsExport', 'maintenanceExport',
        ]]);
    }

    /**
     * Report center landing page — cards for each report type.
     */
    public function index()
    {
        $user = auth()->user();

        $tenantCount = Tenant::query();
        $paymentCount = Payment::query()->where('verified', 'verified');
        $complaintCount = Complaint::query();
        $maintenanceCount = Maintenance::query();

        if ($user->hasRole('Pg_Admin')) {
            $tenantCount->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $paymentCount->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $complaintCount->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            $maintenanceCount->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        $counts = [
            'tenant' => $tenantCount->count(),
            'payment' => $paymentCount->count(),
            'complaint' => $complaintCount->count(),
            'maintenance' => $maintenanceCount->count(),
        ];

        return view('report::index', compact('counts'));
    }

    /**
     * Tenant report — list all tenants with date range + related filters.
     */
    public function tenants()
    {
        if (request()->ajax()) {
            return DataTables::of($this->tenantReportQuery())
                ->addIndexColumn()
                ->addColumn('room_no', fn ($row) => $row->room?->room_no ?? '—')
                ->editColumn('checkin_date', fn ($row) => $row->checkin_date?->format('d-m-Y') ?? '—')
                ->editColumn('expected_checkout_date', fn ($row) => $row->expected_checkout_date?->format('d-m-Y') ?? '—')
                ->editColumn('monthly_rent', fn ($row) => $row->monthly_rent !== null ? '₹'.number_format((float) $row->monthly_rent, 2) : '—')
                ->editColumn('status', fn ($row) => ucfirst($row->status ?? '—'))
                ->escapeColumns([])
                ->make(true);
        }

        return view('report::tenant', ['pgList' => $this->pgList(), 'tenantList' => $this->tenantList(), 'roomList' => $this->roomList()]);
    }

    /**
     * Payment report — approved payments grouped room-wise or tenant-wise.
     */
    public function payments()
    {
        if (request()->ajax()) {
            return DataTables::of($this->paymentReportQuery())
                ->addIndexColumn()
                ->addColumn('room_no', fn ($row) => $row->room?->room_no ?? '—')
                ->addColumn('tenant_name', fn ($row) => $row->tenant?->name ?? '—')
                ->editColumn('payment_count', fn ($row) => number_format((float) $row->payment_count))
                ->editColumn('total_amount', fn ($row) => '₹'.number_format((float) $row->total_amount, 2))
                ->escapeColumns([])
                ->make(true);
        }

        return view('report::payment', ['pgList' => $this->pgList(), 'tenantList' => $this->tenantList(), 'roomList' => $this->roomList()]);
    }

    /**
     * Complaint report — list all complaints with date range + related filters.
     */
    public function complaints()
    {
        if (request()->ajax()) {
            return DataTables::of($this->complaintReportQuery())
                ->addIndexColumn()
                ->addColumn('room_no', fn ($row) => $row->room?->room_no ?? '—')
                ->addColumn('category_name', fn ($row) => $row->category?->service_category_name ?? '—')
                ->addColumn('service_name', fn ($row) => $row->service?->service_name ?? '—')
                ->editColumn('complaint_date', fn ($row) => $row->complaint_date?->format('d-m-Y') ?? '—')
                ->editColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status ?? '—')))
                ->escapeColumns([])
                ->make(true);
        }

        return view('report::complaint', ['pgList' => $this->pgList(), 'roomList' => $this->roomList()]);
    }

    /**
     * Maintenance report — list all maintenance records with date range + related filters.
     */
    public function maintenance()
    {
        if (request()->ajax()) {
            return DataTables::of($this->maintenanceReportQuery())
                ->addIndexColumn()
                ->addColumn('complaint_no', fn ($row) => $row->complaint?->complaint_no ?? '—')
                ->addColumn('room_no', fn ($row) => $row->complaint?->room?->room_no ?? '—')
                ->editColumn('maintenance_date', fn ($row) => $row->maintenance_date?->format('d-m-Y') ?? '—')
                ->editColumn('cost', fn ($row) => $row->cost !== null ? '₹'.number_format((float) $row->cost, 2) : '—')
                ->editColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status ?? '—')))
                ->escapeColumns([])
                ->make(true);
        }

        return view('report::maintenance', ['pgList' => $this->pgList(), 'roomList' => $this->roomList()]);
    }

    /**
     * Download the tenant report as an Excel file.
     */
    public function tenantsExport()
    {
        return Excel::download(new TenantReportExport($this->tenantReportQuery()), 'tenant-report-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Download the payment report as an Excel file.
     * When a tenant is selected, exports each payment record vertically instead of the grouped summary.
     */
    public function paymentsExport()
    {
        $filename = 'payment-report-'.now()->format('Y-m-d').'.xlsx';

        if (request('filter_tenant')) {
            return Excel::download(new PaymentDetailReportExport($this->paymentBaseQuery()), $filename);
        }

        return Excel::download(new PaymentReportExport($this->paymentReportQuery(), $this->paymentMonthlyTotalsQuery()->get()), $filename);
    }

    /**
     * Download the complaint report as an Excel file.
     */
    public function complaintsExport()
    {
        return Excel::download(new ComplaintReportExport($this->complaintReportQuery()), 'complaint-report-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Download the maintenance report as an Excel file.
     */
    public function maintenanceExport()
    {
        return Excel::download(new MaintenanceReportExport($this->maintenanceReportQuery()), 'maintenance-report-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Base query for the tenant report with scoping + filters applied.
     */
    protected function tenantReportQuery()
    {
        $user = auth()->user();

        $query = Tenant::with('pg', 'room', 'permanentState', 'permanentCity')
            ->select('id', 'public_id', 'name', 'email', 'phone', 'pg_id', 'room_id', 'bed_no', 'date_of_birth', 'gender', 'occupation', 'address', 'checkin_date', 'expected_checkout_date', 'monthly_rent', 'security_deposit', 'payment_method', 'id_proof_type', 'id_proof_number', 'emergency_contact_name', 'emergency_relation', 'emergency_contact_number', 'permanent_state_id', 'permanent_city_id', 'permanent_address', 'additional_notes', 'status');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($from = request('filter_from')) {
            $query->where('checkin_date', '>=', $from);
        }

        if ($to = request('filter_to')) {
            $query->where('checkin_date', '<=', $to);
        }

        if ($search = trim((string) request('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        if ($status = request('filter_status')) {
            $query->where('status', $status);
        }

        if ($tenantId = request('filter_tenant')) {
            $query->where('id', $tenantId);
        }

        if ($roomId = request('filter_room')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    /**
     * Base query for the payment report (approved, grouped) with scoping + filters applied.
     */
    protected function paymentBaseQuery()
    {
        $user = auth()->user();

        $query = Payment::query()
            ->with(['room', 'tenant'])
            ->where('verified', 'verified');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($from = request('filter_from')) {
            $query->where('payment_date', '>=', $from);
        }

        if ($to = request('filter_to')) {
            $query->where('payment_date', '<=', $to);
        }

        if ($search = trim((string) request('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('tenant', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($sq) => $sq->where('room_no', 'like', "%{$search}%"))
                    ->orWhereHas('pg', fn ($sq) => $sq->where('pg_name', 'like', "%{$search}%"));
            });
        }

        if ($tenantId = request('filter_tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($roomId = request('filter_room')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    protected function paymentReportQuery()
    {
        return $this->paymentBaseQuery()
            ->select(['room_id', 'tenant_id'])
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupBy(['room_id', 'tenant_id']);
    }

    /**
     * Row query for the last-12-months rent breakdown in the payment export.
     */
    protected function paymentMonthlyTotalsQuery()
    {
        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', payment_date)"
            : "DATE_FORMAT(payment_date, '%Y-%m')";

        return $this->paymentBaseQuery()
            ->select(['room_id', 'tenant_id'])
            ->selectRaw("{$monthExpr} as month")
            ->selectRaw('SUM(amount) as month_amount')
            ->groupBy(['room_id', 'tenant_id', 'month']);
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

        if ($from = request('filter_from')) {
            $query->where('complaint_date', '>=', $from);
        }

        if ($to = request('filter_to')) {
            $query->where('complaint_date', '<=', $to);
        }

        if ($search = trim((string) request('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('complaint_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        if ($status = request('filter_status')) {
            $query->where('status', $status);
        }

        if ($pgId = request('filter_pg')) {
            $query->where('pg_id', $pgId);
        }

        if ($roomId = request('filter_room')) {
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

        if ($from = request('filter_from')) {
            $query->where('maintenance_date', '>=', $from);
        }

        if ($to = request('filter_to')) {
            $query->where('maintenance_date', '<=', $to);
        }

        if ($search = trim((string) request('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('maintenance_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('complaint', fn ($sq) => $sq->where('complaint_no', 'like', "%{$search}%"));
            });
        }

        if ($status = request('filter_status')) {
            $query->where('status', $status);
        }

        if ($pgId = request('filter_pg')) {
            $query->whereHas('complaint', fn ($sq) => $sq->where('pg_id', $pgId));
        }

        if ($roomId = request('filter_room')) {
            $query->whereHas('complaint', fn ($sq) => $sq->where('room_id', $roomId));
        }

        return $query;
    }

    /**
     * PG list for the filter dropdown, scoped to the current user's role.
     */
    protected function pgList()
    {
        $user = auth()->user();
        $query = PgManagement::select('id', 'pg_name')->where('status', 'active');

        if ($user->hasRole('Pg_Admin')) {
            $query->where('owner_id', $user->id);
        }

        return $query->orderBy('pg_name')->get();
    }

    /**
     * Tenant list for the filter dropdown, scoped to the current user's role.
     */
    protected function tenantList()
    {
        $user = auth()->user();
        $query = Tenant::select('id', 'name')->where('status', 'active');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Room list for the filter dropdown, scoped to the current user's role.
     */
    protected function roomList()
    {
        $user = auth()->user();
        $query = Room::select('id', 'room_no', 'pg_id')->where('status', 'active');

        if ($user->hasRole('Pg_Admin')) {
            $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        return $query->orderBy('room_no')->get();
    }
}
