<?php

use Illuminate\Support\Facades\Route;
use Modules\Noticeboard\Http\Controllers\NoticeboardController;

Route::middleware(['auth', 'verified', 'access.type:web'])->group(function () {
    Route::resource('noticeboards', NoticeboardController::class)->names('noticeboard')->except(['create']);
});
