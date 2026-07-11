<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Api\ServiceCategoryApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('service-categories', [ServiceCategoryApiController::class, 'index'])->name('service-categories');
});
