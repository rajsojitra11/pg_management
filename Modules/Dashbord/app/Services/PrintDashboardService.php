<?php

declare(strict_types=1);

namespace Modules\Dashbord\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PrintDashboardService
{
    private function pgFilter(): ?array
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('Pg_Admin')) {
            return null;
        }

        return DB::table('pg_management')
            ->whereNull('deleted_at')
            ->where('owner_id', $user->id)
            ->pluck('id')
            ->all();
    }

    public function kpis(?int $yearId, ?string $sDate = null, ?string $eDate = null): array
    {
        $pgIds = $this->pgFilter();

        $totalPg = (int) DB::table('pg_management')->whereNull('deleted_at')->where('status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('id', $pgIds))
            ->count();

        $totalRooms = (int) DB::table('pg_rooms')->whereNull('deleted_at')->where('status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->count();

        $occupiedRooms = (int) DB::table('tenants')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->whereNotNull('room_id')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->distinct('room_id')
            ->count('room_id');

        $vacantRooms = max(0, $totalRooms - $occupiedRooms);

        $activeTenants = (int) DB::table('tenants')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->count();

        $revenueQuery = DB::table('payments')
            ->whereNull('deleted_at')
            ->where('verified', 'verified')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds));

        if ($sDate && $eDate) {
            $revenueQuery->whereDate('payment_date', '>=', $sDate)
                ->whereDate('payment_date', '<=', $eDate);
        }

        $monthlyRevenue = (float) $revenueQuery->sum('amount');

        return [
            'total_pg' => $totalPg,
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'vacant_rooms' => $vacantRooms,
            'active_tenants' => $activeTenants,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    public function charts(?int $yearId, ?string $sDate = null, ?string $eDate = null): array
    {
        return [
            'revenue_by_month' => $this->revenueByMonth($sDate, $eDate),
            'occupancy_rate' => $this->occupancyRate(),
            'top_pg_tenants' => $this->topPgTenants(),
            'payment_methods' => $this->paymentMethods($sDate, $eDate),
            'room_category_dist' => $this->roomCategoryDist(),
        ];
    }

    private function revenueByMonth(?string $sDate, ?string $eDate): array
    {
        $pgIds = $this->pgFilter();
        $start = Carbon::now()->startOfMonth()->subMonths(11);

        $buckets = [];
        $cursor = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            $buckets[$cursor->format('Y-m')] = 0;
            $cursor->addMonth();
        }

        $rows = DB::table('payments')
            ->whereNull('deleted_at')
            ->where('verified', 'verified')
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->when($sDate, fn ($q) => $q->whereDate('payment_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('payment_date', '<=', $eDate))
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS ym, COALESCE(SUM(amount), 0) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        foreach ($rows as $ym => $total) {
            if (array_key_exists($ym, $buckets)) {
                $buckets[$ym] = (float) $total;
            }
        }

        return [
            'labels' => array_map(fn ($ym) => Carbon::createFromFormat('Y-m', $ym)->format('M'), array_keys($buckets)),
            'data' => array_values($buckets),
        ];
    }

    private function occupancyRate(): array
    {
        $pgIds = $this->pgFilter();

        $totalRooms = (int) DB::table('pg_rooms')->whereNull('deleted_at')->where('status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->count();

        $occupied = (int) DB::table('tenants')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->whereNotNull('room_id')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->distinct('room_id')
            ->count('room_id');

        $vacant = max(0, $totalRooms - $occupied);

        return [
            'labels' => ['Occupied', 'Vacant'],
            'data' => [$occupied, $vacant],
        ];
    }

    private function topPgTenants(): array
    {
        $pgIds = $this->pgFilter();

        $rows = DB::table('tenants')
            ->join('pg_management as pg', 'pg.id', '=', 'tenants.pg_id')
            ->whereNull('tenants.deleted_at')
            ->whereNull('pg.deleted_at')
            ->where('tenants.status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('tenants.pg_id', $pgIds))
            ->selectRaw('pg.pg_name AS name, COUNT(*) AS total')
            ->groupBy('tenants.pg_id', 'pg.pg_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function paymentMethods(?string $sDate, ?string $eDate): array
    {
        $pgIds = $this->pgFilter();

        $rows = DB::table('payments')
            ->whereNull('deleted_at')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('pg_id', $pgIds))
            ->when($sDate, fn ($q) => $q->whereDate('payment_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('payment_date', '<=', $eDate))
            ->selectRaw('payment_method, COUNT(*) AS total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('payment_method')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function roomCategoryDist(): array
    {
        $pgIds = $this->pgFilter();

        $rows = DB::table('pg_rooms as r')
            ->join('pg_room_categories as c', 'c.id', '=', 'r.category_id')
            ->whereNull('r.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('r.status', 'active')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('r.pg_id', $pgIds))
            ->selectRaw('c.category_name AS name, COUNT(*) AS total')
            ->groupBy('r.category_id', 'c.category_name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function recentTenants(?int $yearId, int $limit = 8): array
    {
        $pgIds = $this->pgFilter();

        return DB::table('tenants as t')
            ->leftJoin('pg_management as pg', 'pg.id', '=', 't.pg_id')
            ->leftJoin('pg_rooms as r', 'r.id', '=', 't.room_id')
            ->whereNull('t.deleted_at')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('t.pg_id', $pgIds))
            ->orderByDesc('t.id')
            ->limit($limit)
            ->selectRaw('t.name, t.email, t.phone, t.status, pg.pg_name, r.room_no, t.checkin_date')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'email' => $r->email ?? '',
                'phone' => $r->phone ?? '',
                'status' => $r->status,
                'pg_name' => $r->pg_name ?? '',
                'room_no' => $r->room_no ?? '',
                'checkin_date' => $r->checkin_date ? Carbon::parse($r->checkin_date)->format('d/m/Y') : '',
            ])
            ->all();
    }

    public function recentPayments(?int $yearId, int $limit = 5): array
    {
        $pgIds = $this->pgFilter();

        return DB::table('payments as p')
            ->join('tenants as t', 't.id', '=', 'p.tenant_id')
            ->leftJoin('pg_management as pg', 'pg.id', '=', 'p.pg_id')
            ->whereNull('p.deleted_at')
            ->when($pgIds !== null, fn ($q) => $q->whereIn('p.pg_id', $pgIds))
            ->orderByDesc('p.id')
            ->limit($limit)
            ->selectRaw('p.reference_no, p.amount, p.payment_method, p.payment_date, p.verified, t.name AS tenant_name, pg.pg_name')
            ->get()
            ->map(fn ($r) => [
                'ref_no' => $r->reference_no,
                'tenant' => $r->tenant_name,
                'pg' => $r->pg_name ?? '',
                'amount' => number_format((float) $r->amount, 2),
                'method' => $r->payment_method,
                'date' => $r->payment_date ? Carbon::parse($r->payment_date)->format('d/m/Y') : '',
                'status' => $r->verified,
            ])
            ->all();
    }
}
