@extends('layouts.app-tw')

@section('title', 'Dashboard')
@section('nav-module', 'dashboard')
@section('breadcrumb', 'Dashboard')

@section('pagecss')
<style>
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
                Welcome back, {{ Auth::user()->name }}. PG accommodation at a glance.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="erp-switch">
                <input type="checkbox" id="auto-refresh-toggle" class="erp-switch-input"
                    {{ $autoRefresh ? 'checked' : '' }}>
                <span class="erp-switch-track"><span class="erp-switch-dot"></span></span>
                <span class="text-sm text-zinc-600">Auto-refresh</span>
            </label>

            <button id="btn-refresh-dashboard"
                class="h-9 px-3 sm:px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center transition-colors">
                <i class="fa-solid fa-arrows-rotate mr-1.5 text-xs" id="btn-refresh-icon"></i>
                <span class="hidden sm:inline">Refresh</span>
            </button>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if (Route::has('pgmanagement.index'))
            <a href="{{ route('pgmanagement.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-building mr-1.5 text-xs text-blue-600"></i> PG Management
            </a>
        @endif
        @if (Route::has('room.index'))
            <a href="{{ route('room.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-door-open mr-1.5 text-xs text-purple-600"></i> Rooms
            </a>
        @endif
        @if (Route::has('tenant.index'))
            <a href="{{ route('tenant.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-users mr-1.5 text-xs text-emerald-600"></i> Tenants
            </a>
        @endif
        @if (Route::has('payment.index'))
            <a href="{{ route('payment.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-credit-card mr-1.5 text-xs text-amber-600"></i> Payments
            </a>
        @endif
        @if (Route::has('noticeboard.index'))
            <a href="{{ route('noticeboard.index') }}"
                class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center whitespace-nowrap">
                <i class="fa-solid fa-bullhorn mr-1.5 text-xs text-rose-600"></i> Noticeboard
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
                <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Total PG Properties</span>
                <div class="h-8 w-8 rounded-md bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-building text-blue-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-total-pg">—</p>
            <p class="text-xs text-blue-600 mt-1">Active PG properties</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Total Rooms</span>
                <div class="h-8 w-8 rounded-md bg-purple-50 flex items-center justify-center">
                    <i class="fa-solid fa-door-open text-purple-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-total-rooms">—</p>
            <p class="text-xs text-purple-600 mt-1">Active rooms</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Occupied Rooms</span>
                <div class="h-8 w-8 rounded-md bg-emerald-50 flex items-center justify-center">
                    <i class="fa-solid fa-user-check text-emerald-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-occupied-rooms">—</p>
            <p class="text-xs text-emerald-600 mt-1">Currently occupied</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-amber-600 uppercase tracking-wide">Vacant Rooms</span>
                <div class="h-8 w-8 rounded-md bg-amber-50 flex items-center justify-center">
                    <i class="fa-solid fa-door-closed text-amber-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-vacant-rooms">—</p>
            <p class="text-xs text-amber-600 mt-1">Available to rent</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-rose-600 uppercase tracking-wide">Active Tenants</span>
                <div class="h-8 w-8 rounded-md bg-rose-50 flex items-center justify-center">
                    <i class="fa-solid fa-users text-rose-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-active-tenants">—</p>
            <p class="text-xs text-rose-600 mt-1">Currently staying</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-cyan-600 uppercase tracking-wide">Revenue</span>
                <div class="h-8 w-8 rounded-md bg-cyan-50 flex items-center justify-center">
                    <i class="fa-solid fa-indian-rupee-sign text-cyan-600 text-sm"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-zinc-900" id="kpi-monthly-revenue">—</p>
            <p class="text-xs text-cyan-600 mt-1">Selected period</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">Monthly Revenue</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Revenue over the last 12 months</p>
                </div>
            </div>
            <div style="height: 16rem;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Occupancy Rate</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Occupied vs Vacant rooms</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Secondary Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Top PGs by Tenants</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Active tenant count per PG</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="topPgChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Payment Methods</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Distribution of payment types</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-zinc-900">Room Categories</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Distribution by category</p>
            </div>
            <div style="height: 16rem;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Tenants --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm mb-6">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200">
            <h3 class="text-base font-semibold text-zinc-900">Recent Tenants</h3>
            @if (Route::has('tenant.index'))
                <a href="{{ route('tenant.index') }}"
                    class="text-sm font-medium text-zinc-700 hover:text-zinc-900">
                    View all <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                </a>
            @endif
        </div>
        <div class="p-4 overflow-x-auto">
            <table id="recentTenantsTable" class="w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>PG</th>
                        <th>Room</th>
                        <th>Phone</th>
                        <th>Check-in</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200">
            <h3 class="text-base font-semibold text-zinc-900">Recent Payments</h3>
            @if (Route::has('payment.index'))
                <a href="{{ route('payment.index') }}"
                    class="text-sm font-medium text-zinc-700 hover:text-zinc-900">
                    View all <i class="fa-solid fa-arrow-right text-xs ml-1"></i>
                </a>
            @endif
        </div>
        <div class="p-4 overflow-x-auto">
            <table id="recentPaymentsTable" class="w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ref No.</th>
                        <th>Tenant</th>
                        <th>PG</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
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
