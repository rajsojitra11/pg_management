<?php

use Illuminate\Support\Facades\Route;
use Modules\FoodMenu\Http\Controllers\Api\FoodMenuApiController;
use Modules\FoodMenu\Http\Controllers\Api\FoodMenuItemApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('food-menus', FoodMenuApiController::class)->names('foodmenu');
    Route::post('food-menus/{food_menu}/items', [FoodMenuItemApiController::class, 'store'])->name('foodmenu.items.store');
    Route::delete('food-menu-items/{item}', [FoodMenuItemApiController::class, 'destroy'])->name('foodmenu.items.destroy');
});
