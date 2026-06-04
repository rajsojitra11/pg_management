@extends('layouts.app-tw')

@section('title', 'Dashboard')
@section('nav-module', 'dashboard')
@section('breadcrumb', 'Dashboard')

@section('pagecss')
<style>
    /* Recent-table cells inherit the global `table.dataTable` theme (erp-overrides.css)
       so they match every other listing; only the custom cell-content styles live here. */

    /* Client name avatar (tiny rounded square with user icon) */
    .dashboard-avatar {
        height: 1.75rem;
        width: 1.75rem;
        border-radius: 0.375rem;
        background: var(--erp-bg-muted);
        color: var(--erp-text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .dashboard-client {
        font-weight: 500;
        color: var(--erp-text);
    }

    /* Status pill — base shape; per-status colors are applied via inline style */
    .dashboard-status {
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        border-radius: 0.375rem;
        border: 1px solid transparent;
        padding: 0.125rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
</style>
@endsection

@section('content')
<main data-module="dashboard" data-page="Dashboard" data-breadcrumb="Dashboard">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">Dashboard</h1>
            <p class="text-sm text-zinc-500 mt-1">
                Welcome back, {{ Auth::user()->name }}. Print production at a glance.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Auto-refresh toggle --}}
            <label class="erp-switch">
                <input type="checkbox" id="auto-refresh-toggle" class="erp-switch-input"
                    {{ $autoRefresh ? 'checked' : '' }}>
                <span class="erp-switch-track"><span class="erp-switch-dot"></span></span>
                <span class="text-sm text-zinc-600">Auto-refresh</span>
            </label>

            {{-- Refresh button --}}
            <button id="btn-refresh-dashboard"
                class="h-9 px-3 sm:px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center transition-colors">
                <i class="fa-solid fa-arrows-rotate mr-1.5 text-xs" id="btn-refresh-icon"></i>
                <span class="hidden sm:inline">Refresh</span>
            </button>

            @if (Route::has('orderform.create'))
                <a href="{{ route('orderform.create') }}"
                    class="h-9 px-6 rounded-md text-white text-sm font-medium inline-flex items-center justify-center shadow-sm whitespace-nowrap transition-colors"
                    style="background:#3D52A0; border:1px solid #3D52A0; min-width:180px;"
                    onmouseover="this.style.background='#324690';this.style.borderColor='#324690';"
                    onmouseout="this.style.background='#3D52A0';this.style.borderColor='#3D52A0';">
                    <i class="fa-solid fa-plus mr-1.5 text-xs"></i> New Order Form
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Actions — single row of small buttons --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if (Route::has('deliverychallan.index'))
            <a href="{{ route('deliverychallan.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-truck mr-1.5 text-xs text-emerald-600"></i> Delivery Challan
            </a>
        @endif
        @if (Route::has('client.index'))
            <a href="{{ route('client.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-user-group mr-1.5 text-xs text-purple-600"></i> Clients
            </a>
        @endif
        @if (Route::has('vendor.index'))
            <a href="{{ route('vendor.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-handshake mr-1.5 text-xs text-amber-600"></i> Vendors
            </a>
        @endif
        @if (Route::has('orderform.index'))
            <a href="{{ route('orderform.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-chart-line mr-1.5 text-xs text-rose-600"></i> Job Report
            </a>
        @endif
        @if (Route::has('machine.index'))
            <a href="{{ route('machine.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-industry mr-1.5 text-xs text-cyan-600"></i> Machines
            </a>
        @endif
    </div>

    {{-- Date Filter Bar --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">Quick Select</label>
                <select id="dashboard-date-preset"
                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <option value="current_quarter" selected>Current Quarter</option>
                    <option value="current_year">Current Year</option>
                    <option value="current_month">Current Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_quarter">Last Quarter</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">Start Date</label>
                <div class="flex h-9 w-full rounded-md border border-zinc-200 bg-white focus-within:ring-1 focus-within:ring-zinc-500 focus-within:border-zinc-500 overflow-hidden">
                    <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-calendar"></i></span>
                    <input type="text" id="dashboard-s-date" placeholder="Start Date" readonly
                        class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none cursor-pointer">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">End Date</label>
                <div class="flex h-9 w-full rounded-md border border-zinc-200 bg-white focus-within:ring-1 focus-within:ring-zinc-500 focus-within:border-zinc-500 overflow-hidden">
                    <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-calendar"></i></span>
                    <input type="text" id="dashboard-e-date" placeholder="End Date" readonly
                        class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none cursor-pointer">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button
                    class="h-9 flex-1 px-4 rounded-md text-white text-sm font-medium hover:opacity-90 whitespace-nowrap inline-flex items-center justify-center dashboard-search"
                    style="background:#3D52A0;">
                    <i class="fa-solid fa-magnifying-glass mr-1.5 text-xs"></i> Apply
                </button>
                <button
                    class="h-9 flex-1 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center justify-center dashboard-reset">
                    <i class="fa-solid fa-arrow-rotate-left mr-1.5 text-xs"></i> Reset
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Pending Form</span>
                <div class="h-8 w-8 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-hourglass-half text-blue-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-pending-form">—</p>
            <p class="text-xs text-blue-600 mt-1">Awaiting form fill</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-amber-600 uppercase tracking-wide">Pending Delivery</span>
                <div class="h-8 w-8 rounded-md bg-amber-50 flex items-center justify-center">
                    <i class="fa-solid fa-truck-fast text-amber-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-pending-delivery">—</p>
            <p class="text-xs text-amber-600 mt-1">Ready to dispatch</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Delivery Challan</span>
                <div class="h-8 w-8 rounded-md bg-emerald-50 flex items-center justify-center">
                    <i class="fa-solid fa-truck text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-delivery-challans">—</p>
            <p class="text-xs text-emerald-600 mt-1">Issued this FY</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">In Printing</span>
                <div class="h-8 w-8 rounded-md bg-purple-50 flex items-center justify-center">
                    <i class="fa-solid fa-print text-purple-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-in-printing">—</p>
            <p class="text-xs text-purple-600 mt-1">On machines now</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-rose-600 uppercase tracking-wide">Active Clients</span>
                <div class="h-8 w-8 rounded-md bg-rose-50 flex items-center justify-center">
                    <i class="fa-solid fa-user-group text-rose-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-active-clients">—</p>
            <p class="text-xs text-rose-600 mt-1">This financial year</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-cyan-600 uppercase tracking-wide">Machines</span>
                <div class="h-8 w-8 rounded-md bg-cyan-50 flex items-center justify-center">
                    <i class="fa-solid fa-industry text-cyan-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900"><span id="kpi-machines-online">—</span><span class="text-base text-zinc-400" id="kpi-machines-total"></span></p>
            <p class="text-xs text-cyan-600 mt-1">All online</p>
        </div>
    </div>

    {{-- Charts Row — Monthly Orders + Job Card Status --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">Monthly Order Volume</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Order forms created over the last 12 months</p>
                </div>
                <span
                    class="inline-flex items-center text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                    <i class="fa-solid fa-arrow-up mr-1" style="font-size:10px;"></i> 12.4%
                </span>
            </div>
            <div style="height: 16rem;">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Job Card Status</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Distribution across stages</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Secondary Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Top Clients (FY)</h3>
                <p class="text-xs text-zinc-500 mt-0.5">By order volume</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="clientsChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Production by Machine</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Sheets printed this month</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="machineChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Post-Press Mix</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Lamination · UV · Other</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="postPressChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Job Cards --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm mb-6">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200">
            <h3 class="text-base font-semibold text-zinc-900">Recent Job Cards</h3>
            @if (Route::has('orderform.index'))
                <a href="{{ route('orderform.index') }}"
                    class="text-sm font-medium text-zinc-700 hover:text-zinc-900">
                    View all <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                </a>
            @endif
        </div>
        <div class="p-4 overflow-x-auto">
            <table id="recentJobsTable" class="w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order No.</th>
                        <th>Client</th>
                        <th>Job Title</th>
                        <th>Issue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Recent Delivery Challans --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200">
            <h3 class="text-base font-semibold text-zinc-900">Recent Delivery Challans</h3>
            @if (Route::has('deliverychallan.index'))
                <a href="{{ route('deliverychallan.index') }}"
                    class="text-sm font-medium text-zinc-700 hover:text-zinc-900">
                    View all <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                </a>
            @endif
        </div>
        <div class="p-4 overflow-x-auto">
            <table id="recentChallansTable" class="w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Delivery No.</th>
                        <th>Client</th>
                        <th>Job Card No.</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</main>
@endsection

@section('pagescript')
    <script src="{{ asset('assets-tw/vendor/js/chart.umd.min.js') }}"></script>
    <script src="{{ asset('assets-tw/js/erp-charts.js') }}?v={{ config('app.version', time()) }}"></script>
    <script src="{{ asset('assets/custom/dashboard.js') }}?v={{ config('app.version', time()) }}"></script>
@endsection