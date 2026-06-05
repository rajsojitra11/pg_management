<?php

use Illuminate\Support\Facades\Route;
use Modules\Room\Http\Controllers\RoomCategoryController;
use Modules\Room\Http\Controllers\RoomController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('room-categories', RoomCategoryController::class)->names('room-category')->except(['create']);
    Route::resource('rooms', RoomController::class)->names('room')->except(['create']);
    Route::get('room/categories-by-pg', [RoomController::class, 'categoriesByPg'])->name('room.categories-by-pg');
});
