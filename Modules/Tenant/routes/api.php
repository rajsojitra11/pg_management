<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\Api\TenantApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tenant', TenantApiController::class)->names('tenant');
});
