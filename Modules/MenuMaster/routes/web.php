<?php

use Illuminate\Support\Facades\Route;
use Modules\MenuMaster\Http\Controllers\MenuMasterController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::resource('menumasters', MenuMasterController::class)->names('menumaster');
    // Route::get('menumasters/menus', [MenuMasterController::class, 'newindex'])->name('admin.menus.newindex');
    // Route::get('menumasters/{menuMaster}', [MenuMasterController::class, 'show'])->name('admin.menus.show');

    Route::controller(MenuMasterController::class)->group(function () {
        Route::get('/menumasters', [MenuMasterController::class, 'index'])->name('menumasters.index');
        Route::get('/menumasters/create', [MenuMasterController::class, 'create'])->name('menumasters.create');
        Route::post('/menumasters', [MenuMasterController::class, 'store'])->name('menumasters.store');
        Route::get('/menumasters/{menuMaster}', [MenuMasterController::class, 'show'])->name('menumasters.show');
        Route::get('/menumasters/{menuMaster}/edit', [MenuMasterController::class, 'edit'])->name('menumasters.edit');
        Route::put('/menumasters/{menuMaster}', [MenuMasterController::class, 'update'])->name('menumasters.update');
        Route::delete('/menumasters/{menuMaster}', [MenuMasterController::class, 'destroy'])->name('menumasters.destroy');

        // Additional actions
        Route::post('/menumasters/{menuMaster}/duplicate', 'duplicate')->name('menumasters.duplicate');
        Route::post('/menumasters/{menuMaster}/move', 'move')->name('menumasters.move');

        // Utility routes
        Route::post('/menumasters/normalize-orders', 'normalizeOrders')->name('menumasters.normalize-orders');
        Route::post('/menumasters/rebuild-hierarchy', 'rebuildHierarchy')->name('menumasters.rebuild-hierarchy');
        Route::get('/menumasters/export', 'export')->name('menumasters.export');
        Route::get('/menumasters/statistics', 'getStatistics')->name('menumasters.statistics');
    });
});
