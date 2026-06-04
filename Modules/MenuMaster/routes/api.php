<?php

use Illuminate\Support\Facades\Route;
use Modules\MenuMaster\Http\Controllers\MenuMasterController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('menumasters', MenuMasterController::class)->names('menumaster');
});
