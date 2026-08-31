<?php

use App\Http\Controllers\CompetitionSubmissionPhotoController;
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
use App\Http\Controllers\Uye\CompetitionController;
use App\Http\Controllers\Uye\DashboardController;
use App\Http\Controllers\Uye\EquipmentController;
use App\Http\Controllers\Uye\NotificationController;
use App\Http\Controllers\Uye\PortfolioController;
use App\Http\Controllers\Uye\ProfileController;
use Illuminate\Support\Facades\Route;

// uye. subdomain — Üye (fotoğrafçı) self-servis alanı. Diğer 3 grubun
// aksine tek grup burada: kendi kaydını oluşturabiliyor (bkz. plan §Kapsam).
// Route adları guard adıyla ÖNEKLENMİYOR (bare `login`/`register`/`dashboard`)
// — Üye framework'ün varsayılan `web` guard'ı, bootstrap/app.php'deki
// redirectGuestsTo() default dalı da bu bare isimlere göre kuruldu.
Route::domain(config('domains.uye'))->middleware('maintenance:uye')->group(function () {
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
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('bildirimler')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('{notification}', [NotificationController::class, 'show'])->name('show');
            Route::post('tumunu-okundu-isaretle', [NotificationController::class, 'markAllRead'])->name('read-all');
        });

        Route::prefix('yarismalar')->name('competitions.')->group(function () {
            Route::get('/', [CompetitionController::class, 'index'])->name('index');
            Route::get('katilimlarim', [CompetitionController::class, 'entries'])->name('entries');
            Route::get('{competition}', [CompetitionController::class, 'show'])->name('show');
            Route::post('{competition}/katil', [CompetitionController::class, 'start'])->middleware('throttle:10,1')->name('start');
            Route::get('katilim/{entry}', [CompetitionController::class, 'entry'])->name('entry.show');
            Route::post('katilim/{entry}/kategori', [CompetitionController::class, 'addCategory'])->middleware('throttle:30,1')->name('entry.categories.store');
            Route::post('basvuru/{submission}/portfolyo', [CompetitionController::class, 'addPortfolioPhoto'])->middleware('throttle:20,1')->name('submission.portfolio.store');
            Route::post('basvuru/{submission}/yukle', [CompetitionController::class, 'uploadPhoto'])->middleware('throttle:10,1')->name('submission.upload');
            Route::get('fotograf/{submissionPhoto}/goruntule', CompetitionSubmissionPhotoController::class)->middleware('throttle:120,1')->name('photos.show');
            Route::delete('fotograf/{submissionPhoto}', [CompetitionController::class, 'removePhoto'])->middleware('throttle:30,1')->name('submission.photos.destroy');
            Route::post('katilim/{entry}/gonder', [CompetitionController::class, 'submit'])->middleware('throttle:5,1')->name('entry.submit');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

            Route::get('sifre', [ProfileController::class, 'passwordEdit'])->name('password.edit');
            Route::get('hesap', [ProfileController::class, 'accountEdit'])->name('account.edit');
            Route::patch('bildirim-tercihleri', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
        });

        Route::prefix('portfolyo')->name('portfolio.')->group(function () {
            Route::get('/', [PortfolioController::class, 'index'])->name('index');
            Route::get('yeni', [PortfolioController::class, 'create'])->name('create');
            Route::post('/', [PortfolioController::class, 'store'])->name('store');
            Route::get('{photo}/duzenle', [PortfolioController::class, 'edit'])->name('edit');
            Route::patch('{photo}', [PortfolioController::class, 'update'])->name('update');
            Route::delete('{photo}', [PortfolioController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ekipmanlarim')->name('equipment.')->group(function () {
            Route::get('/', [EquipmentController::class, 'index'])->name('index');
            Route::get('yeni', [EquipmentController::class, 'create'])->name('create');
            Route::post('/', [EquipmentController::class, 'store'])->name('store');
            Route::get('{userEquipment}/duzenle', [EquipmentController::class, 'edit'])->name('edit');
            Route::patch('{userEquipment}', [EquipmentController::class, 'update'])->name('update');
            Route::delete('{userEquipment}', [EquipmentController::class, 'destroy'])->name('destroy');
            Route::get('markalar/{equipmentBrand}/modeller', [EquipmentController::class, 'modelsByBrand'])->name('models-by-brand');
        });
    });
});
