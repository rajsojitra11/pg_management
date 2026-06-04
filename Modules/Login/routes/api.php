<?php

use Illuminate\Support\Facades\Route;
use Modules\Login\Http\Controllers\LoginController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('logins', LoginController::class)->names('login');
});
