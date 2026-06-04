<?php

use Illuminate\Support\Facades\Route;
use Modules\Year\Http\Controllers\YearController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('years', YearController::class)->names('year')->except(['show']);
});
