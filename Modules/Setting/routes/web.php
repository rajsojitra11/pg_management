<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('settings', SettingController::class)->names('setting')->except(['show']);
    Route::post('settings/clear-storage', [SettingController::class, 'clearStorage'])->name('setting.clear-storage');
});
