<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\ProfileApiController;
use Modules\User\Http\Controllers\UserController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('user');
    Route::get('profile', [ProfileApiController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileApiController::class, 'update'])->name('profile.update');
});
