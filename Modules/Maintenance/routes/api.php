<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\Api\MaintenanceApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('maintenances', MaintenanceApiController::class)->names('maintenance');
    Route::patch('maintenances/{maintenance}/status', [MaintenanceApiController::class, 'updateStatus'])->name('maintenance.update-status');
});
