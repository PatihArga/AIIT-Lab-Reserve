<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // No public registration — accounts created by admin only

    // Step 1: enter study program email
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'detectStudyProgram']);

    // Step 2: select user from study program + password
    Route::get('login/select', [AuthenticatedSessionController::class, 'selectUser'])
        ->name('login.select');
    Route::post('login/authenticate', [AuthenticatedSessionController::class, 'authenticate'])
        ->name('login.authenticate');

    // Admin login — direct email + password
    Route::get('admin/login', fn() => view('auth.admin-login'))
        ->name('admin.login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'adminAuthenticate'])
        ->name('admin.login.authenticate');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
