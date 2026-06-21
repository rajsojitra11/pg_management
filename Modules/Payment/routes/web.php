<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('payments', PaymentController::class)->names('payment');
    Route::post('payments/{payment}/verified', [PaymentController::class, 'toggleVerified'])->name('payment.verified');
});
