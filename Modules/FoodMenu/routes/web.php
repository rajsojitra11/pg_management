<?php

use Illuminate\Support\Facades\Route;
use Modules\FoodMenu\Http\Controllers\FoodMenuController;
use Modules\FoodMenu\Http\Controllers\FoodMenuItemController;

Route::middleware(['auth', 'verified', 'access.type:web'])->group(function () {
    Route::resource('food-menus', FoodMenuController::class)->names('foodmenu')->except(['create']);
    Route::get('food-menu-items', [FoodMenuItemController::class, 'index'])->name('foodmenu.items');
    Route::post('food-menu-items', [FoodMenuItemController::class, 'store'])->name('foodmenu.items.store');
    Route::delete('food-menu-items/{item}', [FoodMenuItemController::class, 'destroy'])->name('foodmenu.items.destroy');
});
