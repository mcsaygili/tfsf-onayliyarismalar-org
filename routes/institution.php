<?php

use App\Http\Controllers\Institution\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Institution\Auth\NewPasswordController;
use App\Http\Controllers\Institution\Auth\PasswordResetLinkController;
use App\Http\Controllers\Institution\DashboardController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.institution'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('institution.language');

    Route::middleware('guest:institution')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('institution.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('institution.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('institution.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('institution.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('institution.password.store');
    });

    Route::middleware('auth:institution')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('institution.logout');

        Route::get('/', [DashboardController::class, 'index'])->name('institution.dashboard');
    });
});
