<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Http\Controllers\CountryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('countries', CountryController::class)->names('country');
});
