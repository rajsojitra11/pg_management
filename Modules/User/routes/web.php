<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('users', UserController::class);
    Route::post('assign-user', [UserController::class, 'assignUserWise'])->name('assign-user');
    Route::post('assign-user-store', [UserController::class, 'assignUserStore'])->name('assign-user-store');
    Route::post('assign-user-delete', [UserController::class, 'assignUserRemove'])->name('assign-user-delete');
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::get('profile/change-password', [UserController::class, 'changePasswordPage'])->name('profile.change-password');
    Route::post('profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('profile/avatar-upload', [UserController::class, 'avatarUpload'])->name('profile.avatar-upload');
    Route::post('change-password', [UserController::class, 'changePassword'])->name('change-password');
    Route::get('change/lang', [UserController::class, 'language'])->name('language');
    Route::get('year/lang', [UserController::class, 'yearChange'])->name('years');
    Route::post('change-layout', [UserController::class, 'changeLayout'])->name('change-layout');
    Route::post('change-theme', [UserController::class, 'changeTheme'])->name('change-theme');

    Route::post('user-login-status-change', [UserController::class, 'userBlockUnblock'])->name('user-login-status-change');
    Route::post('user-status-change', [UserController::class, 'userActivateDeactivate'])->name('user-status-change');
    Route::get('profile/activities', [UserController::class, 'activities'])->name('profile.activities');
    Route::post('profile/logout-everywhere', [UserController::class, 'logoutEverywhere'])->name('profile.logout-everywhere');
});
