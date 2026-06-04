<?php

namespace Modules\Dashbord\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Dashbord\Models\DashboardWidget;
use Modules\Dashbord\Models\RoleDashboardConfig;
use Modules\Dashbord\Models\UserDashboardConfig;
use Modules\DispatchOrder\Models\DispatchOrder;
use Modules\Formulation\Models\Formulation;
use Modules\Processorder\Models\Processorder;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductStock;
use Modules\Production\Models\Production;
use Modules\Purchase\Models\Purchase;
use Modules\Rawmaterial\Models\Rawmaterial;
use Modules\Rawmaterial\Models\RawMaterialStock;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\Supplier\Models\Supplier;
use Modules\Testing\Models\Testing;

class DashboardService
{
    /**
     * Format a date value using the system date format.
     */
    private function formatDate($date, bool $withTime = false): ?string
    {
        if (! $date) {
            return null;
        }

        $format = $withTime
            ? config('business.system_date_format', 'd-m-Y h:i:s')
            : config('business.system_date_only_format', 'd-m-Y');

        return Carbon::parse($date)->format($format);
    }

    /**
     * Resolve which dashboard widgets a user should see.
     * Priority: user override > role config > widget defaults.
     * Also checks permissions and global active status.
     */
    public function getWidgetsForUser($user): array
    {
        $allWidgets = DashboardWidget::where('is_active', true)
            ->orderBy('section')
            ->orderBy('default_order')
            ->get();

        $roleIds = $user->roles->pluck('id')->toArray();

        // Eager-load role configs for user's roles
        $roleConfigs = RoleDashboardConfig::whereIn('role_id', $roleIds)
            ->get()
            ->keyBy('widget_id');

        // Eager-load user overrides
        $userConfigs = UserDashboardConfig::where('user_id', $user->id)
            ->get()
            ->keyBy('widget_id');

        $resolved = [];

        foreach ($allWidgets as $widget) {
            // Check permission
            if ($widget->permission) {
                $permissions = array_map('trim', explode(',', $widget->permission));
                if (! $user->canAny($permissions)) {
                    continue;
                }
            }

            // Resolve enabled state: user override > role config > default
            $userCfg = $userConfigs->get($widget->id);
            $roleCfg = $roleConfigs->get($widget->id);

            if ($userCfg && $userCfg->enabled !== null) {
                $enabled = $userCfg->enabled;
                $sortOrder = $userCfg->sort_order ?? ($roleCfg->sort_order ?? $widget->default_order);
            } elseif ($roleCfg) {
                $enabled = $roleCfg->enabled;
                $sortOrder = $roleCfg->sort_order;
            } else {
                $enabled = $widget->default_enabled;
                $sortOrder = $widget->default_order;
            }

            if (! $enabled) {
                continue;
            }

            $resolved[] = [
                'key' => $widget->key,
                'title' => $widget->title,
                'type' => $widget->type,
                'section' => $widget->section,
                'icon' => $widget->icon,
                'icon_bg' => $widget->icon_bg,
                'icon_color' => $widget->icon_color,
                'data_endpoint' => $widget->data_endpoint,
                'sort_order' => $sortOrder,
                'size' => $roleCfg->size ?? 'quarter',
                'config' => $widget->config_json,
            ];
        }

        // Sort by section then sort_order
        usort($resolved, function ($a, $b) {
            $sectionOrder = ['live_status' => 1, 'financial' => 2, 'analytics' => 3, 'data_tables' => 4];
            $sa = $sectionOrder[$a['section']] ?? 99;
            $sb = $sectionOrder[$b['section']] ?? 99;
            if ($sa !== $sb) {
                return $sa - $sb;
            }

            return $a['sort_order'] - $b['sort_order'];
        });

        return $resolved;
    }

    /**
     * Build the JS-friendly widget config for AJAX optimization.
     */
    public function getWidgetConfigForJs(array $widgets): array
    {
        $config = [
            'kpiKeys' => [],
            'financialKeys' => [],
            'chartKeys' => [],
            'tableTypes' => [],
        ];

        foreach ($widgets as $w) {
            switch ($w['type']) {
                case 'kpi':
                    $config['kpiKeys'][] = $w['key'];
                    break;
                case 'financial':
                    $config['financialKeys'][] = $w['key'];
                    break;
                case 'chart':
                    $config['chartKeys'][] = $w['key'];
                    break;
                case 'table':
                    // Extract table type from data_endpoint (e.g., 'table:pending-testing' -> 'pending-testing')
                    $parts = explode(':', $w['data_endpoint']);
                    if (isset($parts[1])) {
                        $config['tableTypes'][] = $parts[1];
                    }
                    break;
            }
        }

        return $config;
    }

    /**
     * Get ALL widgets the user has permission for (both enabled and disabled).
     * Used by the "Customize Dashboard" modal so users can toggle widgets on/off.
     */
    public function getAllWidgetsForUser($user): array
    {
        $allWidgets = DashboardWidget::where('is_active', true)
            ->orderBy('section')
            ->orderBy('default_order')
            ->get();

        $roleIds = $user->roles->pluck('id')->toArray();
        $roleConfigs = RoleDashboardConfig::whereIn('role_id', $roleIds)->get()->keyBy('widget_id');
        $userConfigs = UserDashboardConfig::where('user_id', $user->id)->get()->keyBy('widget_id');

        $result = [];

        foreach ($allWidgets as $widget) {
            // Check permission
            if ($widget->permission) {
                $permissions = array_map('trim', explode(',', $widget->permission));
                if (! $user->canAny($permissions)) {
                    continue;
                }
            }

            // Resolve enabled state
            $userCfg = $userConfigs->get($widget->id);
            $roleCfg = $roleConfigs->get($widget->id);

            if ($userCfg && $userCfg->enabled !== null) {
                $enabled = $userCfg->enabled;
            } elseif ($roleCfg) {
                $enabled = $roleCfg->enabled;
            } else {
                $enabled = $widget->default_enabled;
            }

            $result[] = [
                'key' => $widget->key,
                'title' => $widget->title,
                'section' => $widget->section,
                'icon' => $widget->icon,
                'enabled' => $enabled,
            ];
        }

        return $result;
    }

    /**
     * Get KPI card data, respecting user permissions.
     * Optimized: batches related queries and only computes requested KPIs.
     */
    public function getKpiData($user, ?array $widgetKeys = null): array
    {
        $data = [];
        // Helper: check if a specific KPI widget is requested
        $wants = fn (string $key) => $widgetKeys === null || in_array($key, $widgetKeys);

        if ($wants('kpi_product_stock_alerts') && $user->can('product-list')) {
            $data['product_stock_alerts'] = Product::whereColumn('unrestricted_stock', '<', 'stock_alert_qty')
                ->where('stock_alert_qty', '>', 0)
                ->count();
        }

        if ($wants('kpi_rm_stock_alerts') && $user->can('raw-material-list')) {
            $data['raw_material_stock_alerts'] = Rawmaterial::whereColumn('unrestricted_stock', '<', 'stock_alert_qty')
                ->where('stock_alert_qty', '>', 0)
                ->count();
        }

        if ($wants('kpi_pending_testing') && $user->can('testing-list')) {
            $data['pending_testing'] = Testing::whereNull('testing_result')->count();
        }

        if ($wants('kpi_pending_approvals') && $user->can('formulation-list')) {
            $data['pending_approvals'] = Formulation::whereIn('formulation_status', ['Draft', 'Reviewed'])->count();
        }

        $yearId = getSelectedYear();

        // Batch purchase KPIs into a single query (count + sum)
        $wantsPurchase = $wants('kpi_pending_purchase') || $wants('kpi_outstanding_po');
        if ($wantsPurchase && $user->can('purchase-list')) {
            $purchaseStats = Purchase::where('purchase_status_id', 1)
                ->where('year_id', $yearId)
                ->selectRaw('COUNT(*) as pending_count, COALESCE(SUM(amount), 0) as outstanding_value')
                ->first();
            if ($wants('kpi_pending_purchase')) {
                $data['pending_purchase_orders'] = $purchaseStats->pending_count;
            }
            if ($wants('kpi_outstanding_po')) {
                $data['outstanding_po_value'] = (float) $purchaseStats->outstanding_value;
            }
        }

        // Batch sales KPIs into a single query (count + sum)
        $wantsSales = $wants('kpi_pending_sales') || $wants('kpi_outstanding_so');
        if ($wantsSales && $user->can('sales-order-list')) {
            $salesStats = SalesOrder::where('sales_order_status_id', 1)
                ->where('year_id', $yearId)
                ->selectRaw('COUNT(*) as pending_count, COALESCE(SUM(amount), 0) as outstanding_value')
                ->first();
            if ($wants('kpi_pending_sales')) {
                $data['pending_sales_orders'] = $salesStats->pending_count;
            }
            if ($wants('kpi_outstanding_so')) {
                $data['outstanding_so_value'] = (float) $salesStats->outstanding_value;
            }
        }

        if ($wants('kpi_pending_production') && $user->can('production-list')) {
            $data['pending_productions'] = Production::where('is_tested', 0)->count();
        }

        if ($wants('kpi_expiring_batches') && ($user->can('product-list') || $user->can('raw-material-list'))) {
            $thirtyDaysFromNow = Carbon::now()->addDays(30)->toDateString();
            $today = Carbon::now()->toDateString();
            $count = 0;

            if ($user->can('product-list')) {
                $count += ProductStock::whereBetween('expiry_date', [$today, $thirtyDaysFromNow])
                    ->where('unrestricted_stock', '>', 0)
                    ->count();
            }

            if ($user->can('raw-material-list')) {
                $count += RawMaterialStock::whereBetween('expiry_date', [$today, $thirtyDaysFromNow])
                    ->where('unrestricted_stock', '>', 0)
                    ->count();
            }

            $data['expiring_batches'] = $count;
        }

        return $data;
    }

    /**
     * Get financial summary data, respecting user permissions.
     */
    public function getFinancialData($user, ?string $startDate = null, ?string $endDate = null, ?array $widgetKeys = null): array
    {
        $data = [];
        $yearId = getSelectedYear();
        $wants = fn (string $key) => $widgetKeys === null || in_array($key, $widgetKeys);

        if ($wants('fin_purchase_value') && $user->can('purchase-list')) {
            $query = Purchase::where('year_id', $yearId);
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
            $data['purchase_value'] = (float) $query->sum('amount');
        }

        if ($wants('fin_sales_value') && $user->can('sales-order-list')) {
            $query = SalesOrder::where('year_id', $yearId);
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
            $data['sales_value'] = (float) $query->sum('amount');
        }

        return $data;
    }

    /**
     * Get chart data, respecting user permissions.
     */
    public function getChartData($user, ?string $startDate = null, ?string $endDate = null, ?array $widgetKeys = null): array
    {
        $data = [];
        $wants = fn (string $key) => $widgetKeys === null || in_array($key, $widgetKeys);

        if ($wants('chart_purchase_sales_trend') && $user->can('purchase-list') && $user->can('sales-order-list')) {
            $data['purchase_sales_trend'] = $this->getPurchaseSalesTrend($startDate, $endDate);
        }

        if ($wants('chart_production_yield') && $user->can('production-list')) {
            $data['production_yield'] = $this->getProductionYield($startDate, $endDate);
        }

        if ($wants('chart_top_customers') && $user->can('customer-list') && $user->can('sales-order-list')) {
            $data['top_customers'] = $this->getTopCustomers($startDate, $endDate);
        }

        if ($wants('chart_top_suppliers') && $user->can('supplier-list') && $user->can('purchase-list')) {
            $data['top_suppliers'] = $this->getTopSuppliers($startDate, $endDate);
        }

        return $data;
    }

    /**
     * Get table data by type, respecting user permissions.
     */
    public function getTableData($user, string $type, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $permissionMap = [
            'product-stock-alerts' => 'product-list',
            'raw-material-stock-alerts' => 'raw-material-list',
            'pending-testing' => 'testing-list',
            'pending-process-orders' => 'process-order-list',
            'pending-formulations' => 'formulation-list',
            'pending-productions' => 'production-list',
            'recent-dispatch-orders' => 'dispatch-order-list',
            'expiring-batches' => ['product-list', 'raw-material-list'],
        ];

        if (! isset($permissionMap[$type])) {
            return null;
        }

        $requiredPermission = $permissionMap[$type];

        if (is_array($requiredPermission)) {
            if (! $user->canAny($requiredPermission)) {
                return null;
            }
        } elseif (! $user->can($requiredPermission)) {
            return null;
        }

        return match ($type) {
            'product-stock-alerts' => $this->getProductStockAlerts(),
            'raw-material-stock-alerts' => $this->getRawMaterialStockAlerts(),
            'pending-testing' => $this->getPendingTesting(),
            'pending-process-orders' => $this->getPendingProcessOrders(),
            'pending-formulations' => $this->getPendingFormulations(),
            'pending-productions' => $this->getPendingProductions(),
            'recent-dispatch-orders' => $this->getRecentDispatchOrders($startDate, $endDate),
            'expiring-batches' => $this->getExpiringBatches($user),
            default => null,
        };
    }

    private function getPurchaseSalesTrend(?string $startDate = null, ?string $endDate = null): array
    {
        $yearId = getSelectedYear();

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->endOfMonth();
        } else {
            $start = Carbon::now()->subMonths(5)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        // Single grouped query instead of per-month loop
        $purchaseByMonth = Purchase::where('year_id', $yearId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $salesByMonth = SalesOrder::where('year_id', $yearId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        // Build aligned arrays for all months in range
        $months = [];
        $purchaseData = [];
        $salesData = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $key = $current->format('Y-m');
            $months[] = $current->format('M Y');
            $purchaseData[] = (float) ($purchaseByMonth[$key] ?? 0);
            $salesData[] = (float) ($salesByMonth[$key] ?? 0);
            $current->addMonth();
        }

        return [
            'months' => $months,
            'purchase' => $purchaseData,
            'sales' => $salesData,
        ];
    }

    private function getProductionYield(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Production::select('production_number', 'yield_percentage')
            ->whereNotNull('yield_percentage');

        if ($startDate && $endDate) {
            $query->whereBetween('production_date', [$startDate, $endDate]);
        }

        $productions = $query->latest()->limit(10)->get();

        return [
            'labels' => $productions->pluck('production_number')->toArray(),
            'data' => $productions->pluck('yield_percentage')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    private function getTopCustomers(?string $startDate = null, ?string $endDate = null): array
    {
        $yearId = getSelectedYear();

        $query = SalesOrder::select('customer_id', DB::raw('SUM(amount) as total_amount'))
            ->where('year_id', $yearId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $results = $query->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $customerIds = $results->pluck('customer_id')->toArray();
        $customers = Customer::whereIn('id', $customerIds)->pluck('name', 'id');

        return [
            'labels' => $results->map(fn ($r) => $customers[$r->customer_id] ?? 'Unknown')->toArray(),
            'data' => $results->pluck('total_amount')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    private function getTopSuppliers(?string $startDate = null, ?string $endDate = null): array
    {
        $yearId = getSelectedYear();

        $query = Purchase::select('supplier_id', DB::raw('SUM(amount) as total_amount'))
            ->where('year_id', $yearId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $results = $query->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $supplierIds = $results->pluck('supplier_id')->toArray();
        $suppliers = Supplier::whereIn('id', $supplierIds)->pluck('name', 'id');

        return [
            'labels' => $results->map(fn ($r) => $suppliers[$r->supplier_id] ?? 'Unknown')->toArray(),
            'data' => $results->pluck('total_amount')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    private function getProductStockAlerts(): array
    {
        return Product::whereColumn('unrestricted_stock', '<', 'stock_alert_qty')
            ->where('stock_alert_qty', '>', 0)
            ->select('name', 'code', 'unrestricted_stock', 'stock_alert_qty')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRawMaterialStockAlerts(): array
    {
        return Rawmaterial::whereColumn('unrestricted_stock', '<', 'stock_alert_qty')
            ->where('stock_alert_qty', '>', 0)
            ->select('name', 'code', 'unrestricted_stock', 'stock_alert_qty')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getPendingTesting(): array
    {
        $query = Testing::whereNull('testing_result');

        return $query->select('arn_number', 'testing_date', 'testing_type')
            ->latest('testing_date')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'arn_number' => $item->arn_number,
                'testing_type' => $item->testing_type,
                'testing_date' => $this->formatDate($item->testing_date),
            ])
            ->toArray();
    }

    private function getPendingProcessOrders(): array
    {
        $query = Processorder::where('processorder_status', 'Draft');

        return $query->select('processorder_number', 'date', 'processorder_status')
            ->latest('date')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'processorder_number' => $item->processorder_number,
                'processorder_status' => $item->processorder_status,
                'date' => $this->formatDate($item->date),
            ])
            ->toArray();
    }

    private function getPendingFormulations(): array
    {
        return Formulation::whereIn('formulation_status', ['Draft', 'Reviewed'])
            ->with('product:id,name')
            ->select('id', 'product_id', 'formulation_status', 'formulation_version')
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getPendingProductions(): array
    {
        $query = Production::where('is_tested', 0);

        return $query->with('product:id,name')
            ->select('id', 'production_number', 'product_id', 'production_date', 'yield_percentage')
            ->latest('production_date')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'production_number' => $item->production_number,
                'product' => $item->product ? ['name' => $item->product->name] : null,
                'production_date' => $this->formatDate($item->production_date),
                'yield_percentage' => $item->yield_percentage,
            ])
            ->toArray();
    }

    private function getRecentDispatchOrders(?string $startDate = null, ?string $endDate = null): array
    {
        $yearId = getSelectedYear();

        $query = DispatchOrder::where('year_id', $yearId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->with('customer:id,name')
            ->select('id', 'dispatch_number', 'customer_id', 'date', 'amount', 'status')
            ->latest('date')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'dispatch_number' => $item->dispatch_number,
                'customer' => $item->customer ? ['name' => $item->customer->name] : null,
                'date' => $this->formatDate($item->date),
                'amount' => $item->amount,
                'status' => $item->status,
            ])
            ->toArray();
    }

    private function getExpiringBatches($user): array
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30)->toDateString();
        $today = Carbon::now()->toDateString();
        $batches = [];

        if ($user->can('product-list')) {
            $productBatches = ProductStock::whereBetween('expiry_date', [$today, $thirtyDaysFromNow])
                ->where('unrestricted_stock', '>', 0)
                ->with('product:id,name')
                ->select('id', 'batch_no', 'product_id', 'expiry_date', 'unrestricted_stock')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'batch_no' => $item->batch_no,
                    'name' => $item->product->name ?? '-',
                    'type' => 'Product',
                    'expiry_date' => $this->formatDate($item->expiry_date),
                    'stock' => $item->unrestricted_stock,
                ]);

            $batches = array_merge($batches, $productBatches->toArray());
        }

        if ($user->can('raw-material-list')) {
            $rmBatches = RawMaterialStock::whereBetween('expiry_date', [$today, $thirtyDaysFromNow])
                ->where('unrestricted_stock', '>', 0)
                ->with('raw_material:id,name')
                ->select('id', 'batch_no', 'rawmaterial_id', 'expiry_date', 'unrestricted_stock')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'batch_no' => $item->batch_no,
                    'name' => $item->raw_material->name ?? '-',
                    'type' => 'Raw Material',
                    'expiry_date' => $this->formatDate($item->expiry_date),
                    'stock' => $item->unrestricted_stock,
                ]);

            $batches = array_merge($batches, $rmBatches->toArray());
        }

        usort($batches, fn ($a, $b) => strcmp($a['expiry_date'], $b['expiry_date']));

        return array_slice($batches, 0, 10);
    }
}
