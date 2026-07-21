<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Api\NotificationApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationApiController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationApiController::class, 'markAllRead']);
});
