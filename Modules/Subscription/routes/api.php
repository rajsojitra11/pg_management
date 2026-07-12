<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\Api\SubscriptionApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('subscriptions', SubscriptionApiController::class)->names('subscription.api');
});
