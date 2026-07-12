<?php

use Illuminate\Support\Facades\Route;
use Modules\Noticeboard\Http\Controllers\Api\NoticeboardApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('noticeboards', NoticeboardApiController::class)->names('noticeboard');
});
