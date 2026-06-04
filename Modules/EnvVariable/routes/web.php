<?php

use Illuminate\Support\Facades\Route;
use Modules\EnvVariable\Http\Controllers\EnvVariableController;

Route::middleware(['auth', 'verified', 'role:Super_Admin'])->group(function () {
    // Environment Variable CRUD routes (Super_Admin only)
    Route::resource('env-variables', EnvVariableController::class)->names('env-variable');

    // Additional EnvVariable management routes (Super_Admin only)
    Route::prefix('env-variables')->name('env-variable.')->group(function () {
        // System actions
        Route::post('sync-to-env', [EnvVariableController::class, 'syncToEnv'])->name('sync-to-env');
        Route::post('clear-cache', [EnvVariableController::class, 'clearAllCaches'])->name('clear-cache');
        Route::post('composer-dump', [EnvVariableController::class, 'composerDumpAutoload'])->name('composer-dump');
    });
});
