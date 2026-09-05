<?php

use App\Http\Controllers\CompetitionRegistrationController;
use App\Http\Controllers\CompetitionSubmissionPhotoController;
use App\Http\Controllers\RegistrationExceptionController;
use App\Http\Controllers\SetLanguageController;
use App\Http\Controllers\Temsilci\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Temsilci\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Temsilci\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Temsilci\Auth\NewPasswordController;
use App\Http\Controllers\Temsilci\Auth\PasswordResetLinkController;
use App\Http\Controllers\Temsilci\Auth\RegisteredController;
use App\Http\Controllers\Temsilci\Auth\VerifyEmailController;
use App\Http\Controllers\Temsilci\CompetitionController;
use App\Http\Controllers\Temsilci\DashboardController;
use App\Http\Controllers\Temsilci\ParticipantSubmissionController;
use App\Http\Controllers\Temsilci\PasswordController;
use App\Http\Controllers\Temsilci\ProfileController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.temsilci'))->middleware(['maintenance:temsilci', 'panel.session:temsilci'])->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('temsilci.language');

    Route::middleware('guest:temsilci')->group(function () {
        Route::get('register', [RegisteredController::class, 'create'])->name('temsilci.register');
        Route::post('register', [RegisteredController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('temsilci.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('temsilci.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:10,1')->name('temsilci.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('temsilci.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:30,1')->name('temsilci.password.store');
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
        Route::prefix('on-kayitlar')->name('temsilci.registrations.')->group(function () {
            Route::get('/', [CompetitionRegistrationController::class, 'index'])->name('index');
            Route::get('dogrudan/{competition}', [RegistrationExceptionController::class, 'create'])->name('direct.create');
            Route::post('dogrudan/{competition}/ara', [RegistrationExceptionController::class, 'lookup'])->middleware('throttle:10,1')->name('direct.lookup');
            Route::post('dogrudan/{competition}', [RegistrationExceptionController::class, 'store'])->middleware('throttle:10,1')->name('direct.store');
            Route::get('belgeler/{document}', [CompetitionRegistrationController::class, 'download'])->middleware('throttle:30,1')->name('documents.show');
            Route::get('{registration}', [CompetitionRegistrationController::class, 'review'])->name('show');
            Route::post('{registration}/karar', [CompetitionRegistrationController::class, 'decide'])->middleware('throttle:20,1')->name('decide');
        });

        Route::get('/', [DashboardController::class, 'index'])->name('temsilci.dashboard');

        Route::prefix('yarismalarim')->name('temsilci.competitions.')->group(function () {
            Route::get('/', [CompetitionController::class, 'index'])->name('index');
            Route::get('{competition}', [CompetitionController::class, 'show'])->name('show');
            Route::post('{competition}/izleme-raporu', [CompetitionController::class, 'report'])->name('reports.store');
        });

        Route::prefix('katilimci-onaylari')->name('temsilci.participant-submissions.')->group(function () {
            Route::get('/', [ParticipantSubmissionController::class, 'index'])->name('index');
            Route::get('fotograflar/{submissionPhoto}', CompetitionSubmissionPhotoController::class)->name('photos.show');
            Route::get('{approval}', [ParticipantSubmissionController::class, 'show'])->name('show');
            Route::post('{approval}/karar', [ParticipantSubmissionController::class, 'decide'])->name('decide');
        });

        Route::get('temsilci-bilgilerim', [ProfileController::class, 'edit'])->name('temsilci.profile.edit');
        Route::patch('temsilci-bilgilerim', [ProfileController::class, 'update'])->name('temsilci.profile.update');

        Route::get('temsilci-sifrem', [PasswordController::class, 'edit'])->name('temsilci.password.edit');
        Route::put('temsilci-sifrem', [PasswordController::class, 'update'])->name('temsilci.password.update');
    });
});
