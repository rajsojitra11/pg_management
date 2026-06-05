<?php

use Illuminate\Support\Facades\Route;
use Modules\PgManagement\Http\Controllers\PgManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('pg-management', PgManagementController::class)->names('pgmanagement');
});
