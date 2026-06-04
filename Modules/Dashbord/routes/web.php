<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashbord\Http\Controllers\DashbordController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashbordController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/kpi-data', [DashbordController::class, 'getKpiData'])->name('dashboard.kpi-data');
    Route::get('/dashboard/chart-data', [DashbordController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/table-data/{type}', [DashbordController::class, 'getTableData'])->name('dashboard.table-data');
    Route::post('/dashboard/toggle-auto-refresh', [DashbordController::class, 'toggleAutoRefresh'])->name('dashboard.toggle-auto-refresh');

    // Dashboard widget configuration
    Route::get('/dashboard/settings', [DashbordController::class, 'settings'])->name('dashboard.settings');
    Route::post('/dashboard/config/role', [DashbordController::class, 'saveRoleConfig'])->name('dashboard.config.role');
    Route::post('/dashboard/config/user', [DashbordController::class, 'saveUserConfig'])->name('dashboard.config.user');
});
