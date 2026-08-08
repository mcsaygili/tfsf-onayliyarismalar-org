<?php

use App\Http\Controllers\Temsilci\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Temsilci\Auth\NewPasswordController;
use App\Http\Controllers\Temsilci\Auth\PasswordResetLinkController;
use App\Http\Controllers\Temsilci\DashboardController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.temsilci'))->group(function () {
    Route::middleware('guest:temsilci')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('temsilci.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('temsilci.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('temsilci.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('temsilci.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('temsilci.password.store');
    });

    Route::middleware('auth:temsilci')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('temsilci.logout');

        Route::get('/', [DashboardController::class, 'index'])->name('temsilci.dashboard');
    });
});
