<?php

use Illuminate\Support\Facades\Route;
use Modules\Room\Http\Controllers\Api\RoomApiController;
use Modules\Room\Http\Controllers\Api\RoomCategoryApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('room-categories', RoomCategoryApiController::class)->names('room-category');
    Route::apiResource('rooms', RoomApiController::class)->names('room');
    Route::get('room/categories-by-pg', [RoomApiController::class, 'categoriesByPg'])->name('room.categories-by-pg');
});
