<?php

use Illuminate\Support\Facades\Route;
use Modules\Year\Http\Controllers\YearController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Additional year management routes (before resource routes to avoid conflicts)
    Route::get('years/search', [YearController::class, 'searchYears'])->name('years.search');
    Route::get('years/current-fiscal', [YearController::class, 'getCurrentFiscal'])->name('years.current-fiscal');
    Route::get('years/session/{yearId}', [YearController::class, 'setSessionYear'])->name('years.session');

    Route::resource('years', YearController::class)->names('year')->except(['create', 'show']);
});
