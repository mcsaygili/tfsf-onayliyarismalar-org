<?php

use App\Http\Controllers\Institution\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Institution\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Institution\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Institution\Auth\NewPasswordController;
use App\Http\Controllers\Institution\Auth\PasswordResetLinkController;
use App\Http\Controllers\Institution\Auth\RegisteredController;
use App\Http\Controllers\Institution\Auth\VerifyEmailController;
use App\Http\Controllers\Institution\DashboardController;
use App\Http\Controllers\Institution\ProfileController;
use App\Http\Controllers\Institution\StaffController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.institution'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('institution.language');

    Route::middleware('guest:institution')->group(function () {
        Route::get('register', [RegisteredController::class, 'create'])->name('institution.register');
        Route::post('register', [RegisteredController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('institution.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('institution.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('institution.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('institution.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('institution.password.store');
    });

    Route::middleware('auth:institution')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)->name('institution.verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('institution.verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('institution.verification.send');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('institution.logout');
    });

    Route::middleware(['auth:institution', 'verified.guard:institution'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('institution.dashboard');

        Route::get('kurum-bilgileri', [ProfileController::class, 'edit'])->name('institution.profile.edit');
        Route::patch('kurum-bilgileri', [ProfileController::class, 'update'])->name('institution.profile.update');

        Route::prefix('yetkililer')->name('institution.staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('yeni', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('{staff}/duzenle', [StaffController::class, 'edit'])->name('edit');
            Route::patch('{staff}', [StaffController::class, 'update'])->name('update');
        });
    });
});
