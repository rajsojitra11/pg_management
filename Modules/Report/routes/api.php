<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\Api\ReportApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('reports/summary', [ReportApiController::class, 'summary'])->name('report.summary');
    Route::get('reports/tenants', [ReportApiController::class, 'tenants'])->name('report.tenants');
    Route::get('reports/payments', [ReportApiController::class, 'payments'])->name('report.payments');
    Route::get('reports/complaints', [ReportApiController::class, 'complaints'])->name('report.complaints');
    Route::get('reports/maintenance', [ReportApiController::class, 'maintenance'])->name('report.maintenance');
});
