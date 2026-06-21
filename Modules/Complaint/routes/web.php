<?php

use Illuminate\Support\Facades\Route;
use Modules\Complaint\Http\Controllers\ComplaintController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('complaints', ComplaintController::class)->names('complaint')->except(['create']);
    Route::get('complaint/services-by-category', [ComplaintController::class, 'servicesByCategory'])->name('complaint.services-by-category');
    Route::get('complaint/next-number', [ComplaintController::class, 'nextComplaintNo'])->name('complaint.next-number');
});
