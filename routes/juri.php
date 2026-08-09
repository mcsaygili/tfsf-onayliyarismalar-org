<?php

use App\Http\Controllers\Juri\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Juri\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Juri\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Juri\Auth\NewPasswordController;
use App\Http\Controllers\Juri\Auth\PasswordResetLinkController;
use App\Http\Controllers\Juri\Auth\RegisteredController;
use App\Http\Controllers\Juri\Auth\VerifyEmailController;
use App\Http\Controllers\Juri\DashboardController;
use App\Http\Controllers\Juri\ProfileController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.juri'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('juri.language');

    Route::middleware('guest:juri')->group(function () {
        Route::get('register', [RegisteredController::class, 'create'])->name('juri.register');
        Route::post('register', [RegisteredController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('juri.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('juri.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('juri.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('juri.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('juri.password.store');
    });

    Route::middleware('auth:juri')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)->name('juri.verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('juri.verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('juri.verification.send');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('juri.logout');
    });

    Route::middleware(['auth:juri', 'verified.guard:juri'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('juri.dashboard');

        Route::get('juri-bilgilerim', [ProfileController::class, 'edit'])->name('juri.profile.edit');
        Route::patch('juri-bilgilerim', [ProfileController::class, 'update'])->name('juri.profile.update');
    });
});
