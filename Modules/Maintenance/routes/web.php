<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\MaintenanceController;

Route::middleware(['auth'])->group(function () {
    Route::resource('maintenance', MaintenanceController::class)->except('create');
    Route::get('maintenance/next-no', [MaintenanceController::class, 'nextMaintenanceNo'])->name('maintenance.next-no');
});
