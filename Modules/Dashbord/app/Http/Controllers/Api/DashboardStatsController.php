<?php

namespace Modules\Dashbord\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Complaint\Models\Complaint;
use Modules\Maintenance\Models\Maintenance;
use Modules\Payment\Models\Payment;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;

class DashboardStatsController extends Controller
{
    /**
     * Aggregate counts for the mobile dashboard, scoped by PG.
     */
    public function stats()
    {
        $user = auth()->user();
        $pgId = request('pg_id') ? (int) request('pg_id') : null;

        $scopedByAdmin = function ($query) use ($user) {
            if ($user->hasRole('Pg_Admin')) {
                $query->whereHas('pg', fn ($q) => $q->where('owner_id', $user->id));
            }

            return $query;
        };

        $tenantQuery = $scopedByAdmin(Tenant::query());
        $roomQuery = $scopedByAdmin(Room::query());
        $paymentQuery = $scopedByAdmin(Payment::query());
        $complaintQuery = $scopedByAdmin(Complaint::query());
        $maintenanceQuery = Maintenance::query();

        if ($user->hasRole('Pg_Admin')) {
            $maintenanceQuery->whereHas('complaint.pg', fn ($q) => $q->where('owner_id', $user->id));
        }

        if ($pgId) {
            $tenantQuery->where('pg_id', $pgId);
            $roomQuery->where('pg_id', $pgId);
            $paymentQuery->where('pg_id', $pgId);
            $complaintQuery->where('pg_id', $pgId);
            $maintenanceQuery->whereHas('complaint', fn ($q) => $q->where('pg_id', $pgId));
        }

        $totalTenants = $tenantQuery->count();

        $totalRooms = (clone $roomQuery)->count();
        $occupiedRooms = (clone $roomQuery)
            ->withCount(['tenants as active_tenants_count' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->filter(fn ($room) => $room->active_tenants_count >= (int) ($room->bed_capacity ?? 1))
            ->count();
        $availableRooms = max(0, $totalRooms - $occupiedRooms);

        $totalApprovedPayment = (float) $paymentQuery
            ->where('verified', 'verified')
            ->sum('amount');

        $openComplaints = $complaintQuery
            ->whereNotIn('status', ['resolved'])
            ->count();

        $totalMaintenanceCost = (float) $maintenanceQuery
            ->whereNotIn('status', ['cancelled'])
            ->sum('cost');

        return response()->json([
            'data' => [
                'total_tenants' => $totalTenants,
                'available_rooms' => $availableRooms,
                'total_approved_payment' => $totalApprovedPayment,
                'open_complaints' => $openComplaints,
                'total_maintenance_cost' => $totalMaintenanceCost,
            ],
        ]);
    }
}
