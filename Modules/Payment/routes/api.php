<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Api\PaymentApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('payments/{payment}/toggle-verified', [PaymentApiController::class, 'toggleVerified'])->name('payment.toggle-verified');
    Route::get('payments/pending', [PaymentApiController::class, 'pendingPayments'])->name('payment.pending');
    Route::apiResource('payments', PaymentApiController::class)->names('payment');
});
