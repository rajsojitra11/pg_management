<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashbord\Http\Controllers\DashbordController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('dashbords', DashbordController::class)->names('dashbord');
});
