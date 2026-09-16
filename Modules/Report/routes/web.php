<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('report.index');
    Route::get('report/tenants', [ReportController::class, 'tenants'])->name('report.tenants');
    Route::get('report/payments', [ReportController::class, 'payments'])->name('report.payments');
    Route::get('report/complaints', [ReportController::class, 'complaints'])->name('report.complaints');
    Route::get('report/maintenance', [ReportController::class, 'maintenance'])->name('report.maintenance');

    Route::get('reports/export/tenants', [ReportController::class, 'tenantsExport'])->name('report.tenants.export');
    Route::get('reports/export/payments', [ReportController::class, 'paymentsExport'])->name('report.payments.export');
    Route::get('reports/export/complaints', [ReportController::class, 'complaintsExport'])->name('report.complaints.export');
    Route::get('reports/export/maintenance', [ReportController::class, 'maintenanceExport'])->name('report.maintenance.export');
});
