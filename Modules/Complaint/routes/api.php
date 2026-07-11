<?php

use Illuminate\Support\Facades\Route;
use Modules\Complaint\Http\Controllers\Api\ComplaintApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('complaints', ComplaintApiController::class)->names('complaint');
    Route::get('complaint/services-by-category', [ComplaintApiController::class, 'servicesByCategory'])->name('complaint.services-by-category');
});
