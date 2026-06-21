<?php

use Illuminate\Support\Facades\Route;
use Modules\Email\Http\Controllers\EmailController;

Route::middleware(['auth'])->group(function () {
    Route::get('email', [EmailController::class, 'index'])->name('email.index');

    Route::get('email/config-data', [EmailController::class, 'configData'])->name('email.config-data');
    Route::post('email/config', [EmailController::class, 'storeConfig'])->name('email.config.store');
    Route::get('email/config/{id}/edit', [EmailController::class, 'editConfig'])->name('email.config.edit');
    Route::put('email/config/{id}', [EmailController::class, 'updateConfig'])->name('email.config.update');
    Route::delete('email/config/{id}', [EmailController::class, 'destroyConfig'])->name('email.config.destroy');

    Route::get('email/template', [EmailController::class, 'getTemplate'])->name('email.template.get');
    Route::post('email/template', [EmailController::class, 'saveTemplate'])->name('email.template.save');
    Route::post('email/template/preview', [EmailController::class, 'previewTemplate'])->name('email.template.preview');
});
