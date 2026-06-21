<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\ServiceCategoryController;
use Modules\Service\Http\Controllers\ServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('service-categories', ServiceCategoryController::class)->names('service-category')->except(['create']);
    Route::resource('services', ServiceController::class)->names('service')->except(['create']);
});
