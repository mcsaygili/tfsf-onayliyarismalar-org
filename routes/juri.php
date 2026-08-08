<?php

use App\Http\Controllers\Juri\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Juri\Auth\NewPasswordController;
use App\Http\Controllers\Juri\Auth\PasswordResetLinkController;
use App\Http\Controllers\Juri\DashboardController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.juri'))->group(function () {
    Route::middleware('guest:juri')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('juri.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('juri.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('juri.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('juri.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('juri.password.store');
    });

    Route::middleware('auth:juri')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('juri.logout');

        Route::get('/', [DashboardController::class, 'index'])->name('juri.dashboard');
    });
});
