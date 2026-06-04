<?php

use Illuminate\Support\Facades\Route;
use Modules\State\Http\Controllers\StateController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('states', StateController::class)->names('state');
});
