<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Http\Controllers\UnitController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('units', UnitController::class)->names('unit');
    Route::post('unit-item-delete', [UnitController::class, 'unitItemDelete'])->name('unit-item-delete');

});
