<?php

use Illuminate\Support\Facades\Route;
use Modules\City\Http\Controllers\CityController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cities', CityController::class)->names('city')->except(['create']);
    Route::post('change-city', [CityController::class, 'show'])->name('change-city');

});
