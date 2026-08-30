<?php

use App\Http\Controllers\CompetitionSubmissionPhotoController;
use App\Http\Controllers\Juri\AssignmentController;
use App\Http\Controllers\Juri\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Juri\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Juri\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Juri\Auth\JuryInvitationController;
use App\Http\Controllers\Juri\Auth\NewPasswordController;
use App\Http\Controllers\Juri\Auth\PasswordResetLinkController;
use App\Http\Controllers\Juri\Auth\VerifyEmailController;
use App\Http\Controllers\Juri\DashboardController;
use App\Http\Controllers\Juri\EvaluationController;
use App\Http\Controllers\Juri\PasswordController;
use App\Http\Controllers\Juri\ProfileController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.juri'))->middleware('maintenance:juri')->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('juri.language');

    Route::middleware('guest:juri')->group(function () {
        Route::get('davet/{token}', [JuryInvitationController::class, 'create'])->name('juri.invitation.accept');
        Route::post('davet/{token}', [JuryInvitationController::class, 'store'])->middleware('throttle:6,1');
        Route::post('davet/{token}/reddet', [JuryInvitationController::class, 'decline'])
            ->middleware('throttle:6,1')->name('juri.invitation.decline');

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
        Route::get('gorevlerim', [AssignmentController::class, 'index'])->name('juri.assignments.index');
        Route::get('gorevlerim/{competition}', [AssignmentController::class, 'show'])->name('juri.assignments.show');
        Route::get('gorevlerim/{competition}/kategori/{category}/degerlendirme', [EvaluationController::class, 'show'])->name('juri.evaluations.show');
        Route::put('gorevlerim/{competition}/kategori/{category}/degerlendirme', [EvaluationController::class, 'save'])->name('juri.evaluations.save');
        Route::put('gorevlerim/{competition}/kategori/{category}/degerlendirme/tamamla', [EvaluationController::class, 'finalize'])->name('juri.evaluations.finalize');
        Route::get('degerlendirme/fotograflar/{submissionPhoto}', CompetitionSubmissionPhotoController::class)->name('juri.evaluations.photos.show');

        Route::get('juri-bilgilerim', [ProfileController::class, 'edit'])->name('juri.profile.edit');
        Route::patch('juri-bilgilerim', [ProfileController::class, 'update'])->name('juri.profile.update');

        Route::get('juri-sifrem', [PasswordController::class, 'edit'])->name('juri.password.edit');
        Route::put('juri-sifrem', [PasswordController::class, 'update'])->name('juri.password.update');
    });
});
