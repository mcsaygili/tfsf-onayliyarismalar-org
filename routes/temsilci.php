<?php

use App\Http\Controllers\SetLanguageController;
use App\Http\Controllers\Temsilci\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Temsilci\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Temsilci\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Temsilci\Auth\NewPasswordController;
use App\Http\Controllers\Temsilci\Auth\PasswordResetLinkController;
use App\Http\Controllers\Temsilci\Auth\RegisteredController;
use App\Http\Controllers\Temsilci\Auth\VerifyEmailController;
use App\Http\Controllers\Temsilci\DashboardController;
use App\Http\Controllers\Temsilci\PasswordController;
use App\Http\Controllers\Temsilci\ProfileController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.temsilci'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('temsilci.language');

    Route::middleware('guest:temsilci')->group(function () {
        Route::get('register', [RegisteredController::class, 'create'])->name('temsilci.register');
        Route::post('register', [RegisteredController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('temsilci.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('temsilci.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('temsilci.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('temsilci.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('temsilci.password.store');
    });

    Route::middleware('auth:temsilci')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)->name('temsilci.verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('temsilci.verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('temsilci.verification.send');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('temsilci.logout');
    });

    Route::middleware(['auth:temsilci', 'verified.guard:temsilci'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('temsilci.dashboard');

        Route::get('temsilci-bilgilerim', [ProfileController::class, 'edit'])->name('temsilci.profile.edit');
        Route::patch('temsilci-bilgilerim', [ProfileController::class, 'update'])->name('temsilci.profile.update');

        Route::get('temsilci-sifrem', [PasswordController::class, 'edit'])->name('temsilci.password.edit');
        Route::put('temsilci-sifrem', [PasswordController::class, 'update'])->name('temsilci.password.update');
    });
});
