<?php

declare(strict_types=1);

namespace Modules\Dashbord\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Real-data provider for the print-shop dashboard.
 *
 * Everything is scoped to a financial year (year_id) and, optionally, a date
 * range on order_date / delivery_date. Order "stage" is derived from the
 * presence of downstream documents — the same highest-wins logic as
 * Modules\OrderForm\Services\OrderFormStatusResolver — so counts never rely on
 * the possibly-stale order_forms.status column. DB::table is used throughout to
 * avoid cross-module model imports.
 */
class PrintDashboardService
{
    /** Canonical pipeline stages, lowest → highest. */
    private const STAGES = ['Pending', 'Plate', 'Printed', 'Post-Process', 'Delivered'];

    /**
     * SQL CASE expression that resolves an order's stage. $o is the order_forms
     * table alias in the surrounding query. Mirrors OrderFormStatusResolver.
     */
    private function stageCaseSql(string $o): string
    {
        return "CASE
            WHEN EXISTS (SELECT 1 FROM delivery_challans dc WHERE dc.order_form_id = $o.id AND dc.deleted_at IS NULL) THEN 'Delivered'
            WHEN EXISTS (SELECT 1 FROM lamination_orders lo WHERE lo.order_form_id = $o.id AND lo.deleted_at IS NULL)
             AND EXISTS (SELECT 1 FROM uv_orders uo WHERE uo.order_form_id = $o.id AND uo.deleted_at IS NULL) THEN 'Post-Process'
            WHEN EXISTS (SELECT 1 FROM printing_job_details pj WHERE pj.order_form_id = $o.id AND pj.deleted_at IS NULL) THEN 'Printed'
            WHEN EXISTS (SELECT 1 FROM plate_detail_forms pdf WHERE pdf.order_form_id = $o.id AND pdf.deleted_at IS NULL) THEN 'Plate'
            ELSE 'Pending'
        END";
    }

    /** Base order_forms query scoped to FY + optional order_date range. */
    private function ordersInScope(int $yearId, ?string $sDate, ?string $eDate)
    {
        return DB::table('order_forms as o')
            ->whereNull('o.deleted_at')
            ->where('o.year_id', $yearId)
            ->when($sDate, fn ($q) => $q->whereDate('o.order_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('o.order_date', '<=', $eDate));
    }

    /** Mutually-exclusive stage → count map (every stage key present). */
    private function stageBuckets(int $yearId, ?string $sDate, ?string $eDate): array
    {
        $counts = $this->ordersInScope($yearId, $sDate, $eDate)
            ->selectRaw($this->stageCaseSql('o').' AS stage, COUNT(*) AS total')
            ->groupByRaw('stage')
            ->pluck('total', 'stage');

        $buckets = array_fill_keys(self::STAGES, 0);
        foreach ($counts as $stage => $total) {
            $buckets[$stage] = (int) $total;
        }

        return $buckets;
    }

    /** KPI stat cards. */
    public function kpis(int $yearId, ?string $sDate = null, ?string $eDate = null): array
    {
        $buckets = $this->stageBuckets($yearId, $sDate, $eDate);
        $totalOrders = array_sum($buckets);

        $challans = DB::table('delivery_challans')
            ->whereNull('deleted_at')
            ->where('year_id', $yearId)
            ->when($sDate, fn ($q) => $q->whereDate('delivery_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('delivery_date', '<=', $eDate))
            ->count();

        $activeClients = (int) $this->ordersInScope($yearId, $sDate, $eDate)
            ->distinct()
            ->count('o.client_id');

        $machinesTotal = (int) DB::table('machines')->whereNull('deleted_at')->count();
        $machinesOnline = (int) DB::table('machines')->whereNull('deleted_at')->where('status', 'Active')->count();

        return [
            'pending_form' => $buckets['Pending'],
            'pending_delivery' => $totalOrders - $buckets['Delivered'],
            'delivery_challans' => $challans,
            'in_printing' => $buckets['Printed'],
            'active_clients' => $activeClients,
            'machines_online' => $machinesOnline,
            'machines_total' => $machinesTotal,
        ];
    }

    /** Chart payloads (labels + data) ready for erpChart(). */
    public function charts(int $yearId, ?string $sDate = null, ?string $eDate = null): array
    {
        return [
            'orders_by_month' => $this->ordersByMonth(),
            'status_distribution' => $this->statusDistribution($yearId, $sDate, $eDate),
            'top_clients' => $this->topClients($yearId, $sDate, $eDate),
            'production_by_machine' => $this->productionByMachine($yearId, $sDate, $eDate),
            'post_press_mix' => $this->postPressMix($yearId, $sDate, $eDate),
        ];
    }

    /** Rolling last-12-months order volume (by order_date, across FYs). */
    private function ordersByMonth(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(11);

        $buckets = [];
        $cursor = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            $buckets[$cursor->format('Y-m')] = 0;
            $cursor->addMonth();
        }

        $rows = DB::table('order_forms')
            ->whereNull('deleted_at')
            ->whereDate('order_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        foreach ($rows as $ym => $total) {
            if (array_key_exists($ym, $buckets)) {
                $buckets[$ym] = (int) $total;
            }
        }

        return [
            'labels' => array_map(fn ($ym) => Carbon::createFromFormat('Y-m', $ym)->format('M'), array_keys($buckets)),
            'data' => array_values($buckets),
        ];
    }

    /** 5-stage distribution for the status doughnut. */
    private function statusDistribution(int $yearId, ?string $sDate, ?string $eDate): array
    {
        $buckets = $this->stageBuckets($yearId, $sDate, $eDate);

        return [
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
        ];
    }

    /** Top 5 clients by order count. */
    private function topClients(int $yearId, ?string $sDate, ?string $eDate): array
    {
        $rows = $this->ordersInScope($yearId, $sDate, $eDate)
            ->join('clients as c', 'c.id', '=', 'o.client_id')
            ->selectRaw('c.name AS name, COUNT(*) AS total')
            ->groupBy('o.client_id', 'c.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** Sheets printed per machine. */
    private function productionByMachine(int $yearId, ?string $sDate, ?string $eDate): array
    {
        // A printing job carries machine_id only on multi-machine orders. Single-
        // machine orders leave jobs untagged, so fall back to the order's lone
        // machine (order_form_machines) — same rule as the plate-detail prefill.
        $machineExpr = 'COALESCE(j.machine_id, (SELECT MIN(ofm.machine_id) FROM order_form_machines ofm WHERE ofm.order_form_id = o.id AND ofm.deleted_at IS NULL HAVING COUNT(*) = 1))';

        $rows = DB::table('order_form_printing_jobs as j')
            ->join('order_forms as o', 'o.id', '=', 'j.order_form_id')
            ->join('machines as m', 'm.id', '=', DB::raw($machineExpr))
            ->whereNull('j.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('o.year_id', $yearId)
            ->when($sDate, fn ($q) => $q->whereDate('o.order_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('o.order_date', '<=', $eDate))
            ->selectRaw('m.name AS name, COALESCE(SUM(j.total_sheets), 0) AS total')
            ->groupBy('m.id', 'm.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** Post-press selections grouped by category (lamination / uv / …). */
    private function postPressMix(int $yearId, ?string $sDate, ?string $eDate): array
    {
        $rows = DB::table('order_form_post_press_items as i')
            ->join('order_forms as o', 'o.id', '=', 'i.order_form_id')
            ->join('post_press_categories as c', 'c.id', '=', 'i.post_press_category_id')
            ->whereNull('i.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('o.year_id', $yearId)
            ->when($sDate, fn ($q) => $q->whereDate('o.order_date', '>=', $sDate))
            ->when($eDate, fn ($q) => $q->whereDate('o.order_date', '<=', $eDate))
            ->selectRaw('c.name AS name, COUNT(*) AS total')
            ->groupBy('i.post_press_category_id', 'c.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /** Latest job cards with client + resolved stage. */
    public function recentJobCards(int $yearId, int $limit = 8): array
    {
        return DB::table('order_forms as o')
            ->leftJoin('clients as c', 'c.id', '=', 'o.client_id')
            ->whereNull('o.deleted_at')
            ->where('o.year_id', $yearId)
            ->orderByDesc('o.id')
            ->limit($limit)
            ->selectRaw('o.order_no, o.job_name, o.order_date, c.name AS client_name, '.$this->stageCaseSql('o').' AS status')
            ->get()
            ->map(fn ($r) => [
                'no' => $r->order_no,
                'client' => $r->client_name,
                'title' => $r->job_name,
                'issue' => $r->order_date ? Carbon::parse($r->order_date)->format('d/m/Y') : '',
                'status' => $r->status,
            ])
            ->all();
    }

    /** Latest delivery challans with client + parent order no. */
    public function recentChallans(int $yearId, int $limit = 5): array
    {
        return DB::table('delivery_challans as d')
            ->join('order_forms as o', 'o.id', '=', 'd.order_form_id')
            ->leftJoin('clients as c', 'c.id', '=', 'o.client_id')
            ->whereNull('d.deleted_at')
            ->where('d.year_id', $yearId)
            ->orderByDesc('d.id')
            ->limit($limit)
            ->selectRaw('d.challan_no, o.order_no, c.name AS client_name, d.delivery_date')
            ->get()
            ->map(fn ($r) => [
                'no' => $r->challan_no,
                'client' => $r->client_name,
                'job' => $r->order_no,
                'date' => $r->delivery_date ? Carbon::parse($r->delivery_date)->format('d/m/Y') : '',
            ])
            ->all();
    }
}
