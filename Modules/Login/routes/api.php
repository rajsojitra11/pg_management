<?php

use Illuminate\Support\Facades\Route;
use Modules\Login\Http\Controllers\Api\AuthController;
use Modules\Login\Http\Controllers\LoginController;

// Public auth routes (no auth middleware)
Route::prefix('v1/auth')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('logins', LoginController::class)->names('login');
});
