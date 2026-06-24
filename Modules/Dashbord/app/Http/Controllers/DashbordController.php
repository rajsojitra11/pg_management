<?php

namespace Modules\Dashbord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Dashbord\Models\DashboardWidget;
use Modules\Dashbord\Models\RoleDashboardConfig;
use Modules\Dashbord\Models\UserDashboardConfig;
use Modules\Dashbord\Services\PrintDashboardService;
use Modules\Role\Models\Role;
use Modules\Year\Models\Year;

class DashbordController extends Controller
{
    public function __construct(private PrintDashboardService $dashboardService) {}

    /**
     * Display the dashboard page.
     */
    public function index(Request $request)
    {
        $yearId = getSelectedYear();
        $year = Year::find($yearId);
        $yearStart = null;
        $yearEnd = null;

        $fyStartMonth = config('business.fy_start_month', 4);

        if ($year && $year->full_full) {
            $parts = explode('-', $year->full_full);
            $startYear = (int) $parts[0];
            $endYear = (int) $parts[1];

            if ($startYear === $endYear) {
                // Calendar year (Jan-Dec): e.g., "2026-2026"
                $yearStart = $startYear.'-01-01';
                $yearEnd = $endYear.'-12-31';
            } else {
                // Fiscal year (e.g., Apr-Mar): e.g., "2026-2027"
                $yearStart = $startYear.'-'.str_pad($fyStartMonth, 2, '0', STR_PAD_LEFT).'-01';
                // End date: last day of month before FY start, in end year
                $endMonth = $fyStartMonth - 1;
                $endMonthYear = $endYear;
                if ($endMonth <= 0) {
                    $endMonth = 12;
                    $endMonthYear = $endYear - 1;
                }
                $lastDay = Carbon::create($endMonthYear, $endMonth)->daysInMonth;
                $yearEnd = $endMonthYear.'-'.str_pad($endMonth, 2, '0', STR_PAD_LEFT).'-'.str_pad($lastDay, 2, '0', STR_PAD_LEFT);
            }
        }

        $autoRefresh = auth()->user()->profile->auto_refresh ?? 0;

        return view('dashbord::index', compact('yearStart', 'yearEnd', 'autoRefresh', 'fyStartMonth'));
    }

    /**
     * Get KPI card data for the active financial year + optional date range.
     */
    public function getKpiData(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->kpis(
                getSelectedYear(),
                $request->query('s_date'),
                $request->query('e_date')
            )
        );
    }

    /**
     * Get chart data for the active financial year + optional date range.
     */
    public function getChartData(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->charts(
                getSelectedYear(),
                $request->query('s_date'),
                $request->query('e_date')
            )
        );
    }

    /**
     * Get table rows by type (recent-job-cards | recent-delivery-challans).
     */
    public function getTableData(Request $request, string $type): JsonResponse
    {
        $yearId = getSelectedYear();

        $data = match ($type) {
            'recent-tenants' => $this->dashboardService->recentTenants($yearId),
            'recent-payments' => $this->dashboardService->recentPayments($yearId),
            default => null,
        };

        if ($data === null) {
            return response()->json(['error' => 'Invalid type'], 403);
        }

        return response()->json($data);
    }

    /**
     * Toggle auto-refresh preference.
     */
    public function toggleAutoRefresh(Request $request): JsonResponse
    {
        $user = auth()->user();
        $profile = $user->profile;

        if ($profile) {
            $profile->update(['auto_refresh' => $request->boolean('auto_refresh') ? 1 : 0]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Dashboard settings page — role × widget configuration grid.
     *
     * NOTE (LOOKUP-CONSOLIDATION-001 / R-PROJ-016): the `Role::` query below
     * is intentionally NOT migrated to `lookup.roles`. The roles drive the
     * server-rendered tabs + per-role widget panels (one panel per role) —
     * not a `<select>` typeahead. The full role list MUST be rendered
     * server-side for the page to work; lazy-loading via the lookup endpoint
     * would defeat the panel layout. Documented exception.
     */
    public function settings()
    {
        $user = auth()->user();
        if (! $user->hasRole('Super_Admin') && ! $user->hasRole('Pg_Admin')) {
            abort(403);
        }

        $widgets = DashboardWidget::where('is_active', true)
            ->orderBy('section')
            ->orderBy('default_order')
            ->get();

        $excludeRoles = ['Customer'];
        if (! $user->hasRole('Super_Admin')) {
            $excludeRoles[] = 'Super_Admin';
        }
        $roles = Role::whereNotIn('name', $excludeRoles)->orderBy('name')->get();

        // Load existing role configs keyed by "roleId_widgetId"
        $roleConfigs = RoleDashboardConfig::all()
            ->keyBy(fn ($rc) => $rc->role_id.'_'.$rc->widget_id);

        $sections = [
            'live_status' => 'Live Status (KPIs)',
            'financial' => 'Financial Summary',
            'analytics' => 'Analytics & Charts',
            'data_tables' => 'Data Tables',
        ];

        return view('dashbord::settings', compact('widgets', 'roles', 'roleConfigs', 'sections'));
    }

    /**
     * Save role-widget configuration (bulk toggle).
     */
    public function saveRoleConfig(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user->hasRole('Super_Admin') && ! $user->hasRole('Pg_Admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roleId = $request->input('role_id');
        $widgetConfigs = $request->input('widgets', []);

        DB::beginTransaction();
        try {
            foreach ($widgetConfigs as $widgetId => $enabled) {
                RoleDashboardConfig::updateOrCreate(
                    ['role_id' => $roleId, 'widget_id' => $widgetId],
                    ['enabled' => (bool) $enabled, 'sort_order' => 0, 'size' => 'quarter']
                );
            }
            DB::commit();

            // Clear cached widget configs for all users with this role
            $this->clearRoleWidgetCache($roleId);

            return response()->json(['success' => true, 'message' => 'Role configuration saved.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to save configuration.'], 500);
        }
    }

    /**
     * Save user-widget overrides (personal dashboard customization).
     */
    public function saveUserConfig(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $widgetConfigs = $request->input('widgets', []);

        // Resolve widget keys to IDs
        $widgetKeyToId = DashboardWidget::pluck('id', 'key');

        DB::beginTransaction();
        try {
            foreach ($widgetConfigs as $keyOrId => $enabled) {
                $widgetId = $widgetKeyToId[$keyOrId] ?? (is_numeric($keyOrId) ? $keyOrId : null);
                if (! $widgetId) {
                    continue;
                }
                UserDashboardConfig::updateOrCreate(
                    ['user_id' => $userId, 'widget_id' => $widgetId],
                    ['enabled' => (bool) $enabled]
                );
            }
            DB::commit();

            // No cache to clear — widget resolution is always fresh

            return response()->json(['success' => true, 'message' => 'Dashboard customized.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to save.'], 500);
        }
    }

    /**
     * Clear cached widget configs for all users in a role.
     */
    private function clearRoleWidgetCache(int $roleId): void
    {
        $userIds = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->pluck('model_id');

        foreach ($userIds as $uid) {
            Cache::forget('dashboard_widgets_'.$uid);
        }
    }
}
