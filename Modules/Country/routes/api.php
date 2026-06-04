<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Http\Controllers\CountryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('countries', CountryController::class)->names('country');
});
