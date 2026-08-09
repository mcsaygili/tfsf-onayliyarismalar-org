<?php

use App\Http\Controllers\SetLanguageController;
use App\Http\Controllers\Uye\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Uye\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Uye\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Uye\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Uye\Auth\NewPasswordController;
use App\Http\Controllers\Uye\Auth\PasswordController;
use App\Http\Controllers\Uye\Auth\PasswordResetLinkController;
use App\Http\Controllers\Uye\Auth\RegisteredUserController;
use App\Http\Controllers\Uye\Auth\SmsPasswordResetController;
use App\Http\Controllers\Uye\Auth\VerifyEmailController;
use App\Http\Controllers\Uye\ProfileController;
use Illuminate\Support\Facades\Route;

// uye. subdomain — Üye (fotoğrafçı) self-servis alanı. Diğer 3 grubun
// aksine tek grup burada: kendi kaydını oluşturabiliyor (bkz. plan §Kapsam).
// Route adları guard adıyla ÖNEKLENMİYOR (bare `login`/`register`/`dashboard`)
// — Üye framework'ün varsayılan `web` guard'ı, bootstrap/app.php'deki
// redirectGuestsTo() default dalı da bu bare isimlere göre kuruldu.
Route::domain(config('domains.uye'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('language');

    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

        Route::get('forgot-password/sms', [SmsPasswordResetController::class, 'create'])->name('password.sms.request');
        Route::post('forgot-password/sms', [SmsPasswordResetController::class, 'sendCode'])->name('password.sms.send');
        Route::post('forgot-password/sms/verify', [SmsPasswordResetController::class, 'verifyAndReset'])->name('password.sms.verify');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    Route::middleware('auth')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/', function () {
            return view('uye.dashboard');
        })->name('dashboard');

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

            Route::get('sifre', [ProfileController::class, 'passwordEdit'])->name('password.edit');
            Route::get('hesap', [ProfileController::class, 'accountEdit'])->name('account.edit');
        });
    });
});
