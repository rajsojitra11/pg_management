<?php

use Illuminate\Support\Facades\Route;
use Modules\PgManagement\Http\Controllers\PgManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pg-management', PgManagementController::class)->names('pgmanagement')->except(['create']);
});
