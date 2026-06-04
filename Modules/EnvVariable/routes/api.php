<?php

use Illuminate\Support\Facades\Route;
use Modules\EnvVariable\Http\Controllers\EnvVariableController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Environment Variable API routes
    Route::apiResource('env-variables', EnvVariableController::class)->names('api.env-variable');

    // Additional API routes for environment variables
    Route::prefix('env-variables')->name('api.env-variable.')->group(function () {
        // System management endpoints
        Route::post('sync-to-env', [EnvVariableController::class, 'syncToEnv'])->name('sync-to-env');
        Route::post('clear-cache', [EnvVariableController::class, 'clearAllCaches'])->name('clear-cache');
        Route::post('composer-dump', [EnvVariableController::class, 'composerDumpAutoload'])->name('composer-dump');
    });
});
