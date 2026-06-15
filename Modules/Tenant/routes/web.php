<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tenant/{tenant}/payments', [TenantController::class, 'payments'])->name('tenant.payments');
    Route::resource('tenant', TenantController::class)->names('tenant');
});
