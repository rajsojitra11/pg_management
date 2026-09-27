<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\Api\ReportApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('reports/summary', [ReportApiController::class, 'summary'])->name('report.summary');
    Route::get('reports/filter-options', [ReportApiController::class, 'filterOptions'])->name('report.filter-options');
    Route::get('reports/tenants', [ReportApiController::class, 'tenants'])->name('report.tenants');
    Route::get('reports/payments', [ReportApiController::class, 'payments'])->name('report.payments');
    Route::get('reports/complaints', [ReportApiController::class, 'complaints'])->name('report.complaints');
    Route::get('reports/maintenance', [ReportApiController::class, 'maintenance'])->name('report.maintenance');

    Route::get('reports/export/tenants', [ReportApiController::class, 'tenantsExport'])->name('report.tenants.export');
    Route::get('reports/export/payments', [ReportApiController::class, 'paymentsExport'])->name('report.payments.export');
    Route::get('reports/export/complaints', [ReportApiController::class, 'complaintsExport'])->name('report.complaints.export');
    Route::get('reports/export/maintenance', [ReportApiController::class, 'maintenanceExport'])->name('report.maintenance.export');
});
