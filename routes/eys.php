<?php

use App\Http\Controllers\Eys\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Eys\Auth\NewPasswordController;
use App\Http\Controllers\Eys\Auth\PasswordResetLinkController;
use App\Http\Controllers\Eys\CityController;
use App\Http\Controllers\Eys\CountryController;
use App\Http\Controllers\Eys\DashboardController;
use App\Http\Controllers\Eys\EducationLevelController;
use App\Http\Controllers\Eys\FileManagerController;
use App\Http\Controllers\Eys\InstitutionTypeController;
use App\Http\Controllers\Eys\MailClientController;
use App\Http\Controllers\Eys\PermissionController;
use App\Http\Controllers\Eys\RoleController;
use App\Http\Controllers\Eys\UserController;
use App\Http\Controllers\Eys\UserRoleController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

/**
 * EYS (Elektronik Yönetim Sistemi) — sistemi yönetecek kullanıcıların
 * girdiği panel. Diğer gruplardan farklı olarak herkese açık bir 'register'
 * route'u YOK: yeni EYS kullanıcıları sadece panel içinden, mevcut bir EYS
 * kullanıcısı tarafından oluşturuluyor (bkz. UserController).
 */
Route::domain(config('domains.eys'))->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('eys.language');

    Route::middleware('guest:eys')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('eys.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('eys.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('eys.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('eys.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('eys.password.store');
    });

    // Oturum açmış her EYS kullanıcısı için Spatie "team" context'i sabit
    // olarak Eys modülüne ayarlanıyor (bkz. SetPermissionsTeam) — hem
    // Roller/İzinler route'ları hem de sidebar'daki @can() kontrolleri
    // (her sayfada render edilir) doğru team'de çalışsın diye. Bir modülün
    // kendi verisini yöneten EYS ekranları eklendiğinde (bkz. proje planı),
    // o route grubu kendi 'team:Institution' vb. middleware'iyle bunu
    // override edecek.
    Route::middleware(['auth:eys', 'team:Eys'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('eys.logout');

        Route::get('/', [DashboardController::class, 'index'])->name('eys.dashboard');

        Route::prefix('kullanicilar')->name('eys.users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('yeni', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('{user}/duzenle', [UserController::class, 'edit'])->name('edit');
            Route::patch('{user}', [UserController::class, 'update'])->name('update');
        });

        Route::prefix('kullanicilar/{user}/roller')->name('eys.users.roles.')->middleware('permission:eys.roles.manage')->group(function () {
            Route::get('/', [UserRoleController::class, 'edit'])->name('edit');
            Route::patch('/', [UserRoleController::class, 'update'])->name('update');
        });

        Route::prefix('roller')->name('eys.roles.')->middleware('permission:eys.roles.manage')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('yeni', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('{role}/duzenle', [RoleController::class, 'edit'])->name('edit');
            Route::patch('{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('izinler')->name('eys.permissions.')->middleware('permission:eys.permissions.manage')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::get('yeni', [PermissionController::class, 'create'])->name('create');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::get('{permission}/duzenle', [PermissionController::class, 'edit'])->name('edit');
            Route::patch('{permission}', [PermissionController::class, 'update'])->name('update');
            Route::delete('{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ulkeler')->name('eys.countries.')->middleware('permission:eys.countries.manage')->group(function () {
            Route::get('/', [CountryController::class, 'index'])->name('index');
            Route::get('yeni', [CountryController::class, 'create'])->name('create');
            Route::post('/', [CountryController::class, 'store'])->name('store');
            Route::get('{country}/duzenle', [CountryController::class, 'edit'])->name('edit');
            Route::patch('{country}', [CountryController::class, 'update'])->name('update');
            Route::delete('{country}', [CountryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('sehirler')->name('eys.cities.')->middleware('permission:eys.cities.manage')->group(function () {
            Route::get('/', [CityController::class, 'index'])->name('index');
            Route::get('ulke/{country}', [CityController::class, 'byCountry'])->name('by-country');
            Route::get('yeni', [CityController::class, 'create'])->name('create');
            Route::post('/', [CityController::class, 'store'])->name('store');
            Route::get('{city}/duzenle', [CityController::class, 'edit'])->name('edit');
            Route::patch('{city}', [CityController::class, 'update'])->name('update');
            Route::delete('{city}', [CityController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ogrenim-durumlari')->name('eys.education-levels.')->middleware('permission:eys.education_levels.manage')->group(function () {
            Route::get('/', [EducationLevelController::class, 'index'])->name('index');
            Route::get('yeni', [EducationLevelController::class, 'create'])->name('create');
            Route::post('/', [EducationLevelController::class, 'store'])->name('store');
            Route::get('{educationLevel}/duzenle', [EducationLevelController::class, 'edit'])->name('edit');
            Route::patch('{educationLevel}', [EducationLevelController::class, 'update'])->name('update');
            Route::delete('{educationLevel}', [EducationLevelController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('kurum-turleri')->name('eys.institution-types.')->middleware('permission:eys.institution_types.manage')->group(function () {
            Route::get('/', [InstitutionTypeController::class, 'index'])->name('index');
            Route::get('yeni', [InstitutionTypeController::class, 'create'])->name('create');
            Route::post('/', [InstitutionTypeController::class, 'store'])->name('store');
            Route::get('{institutionType}/duzenle', [InstitutionTypeController::class, 'edit'])->name('edit');
            Route::patch('{institutionType}', [InstitutionTypeController::class, 'update'])->name('update');
            Route::delete('{institutionType}', [InstitutionTypeController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('mail-istemcisi')->name('eys.mail-client.')->middleware('permission:eys.mail_client.view')->group(function () {
            Route::get('/', [MailClientController::class, 'dashboard'])->name('dashboard');
            Route::get('gunlukler', [MailClientController::class, 'logs'])->name('logs');
            Route::get('etkinlik', [MailClientController::class, 'activity'])->name('activity');

            Route::middleware('permission:eys.mail_client.manage')->group(function () {
                Route::get('ayarlar', [MailClientController::class, 'settings'])->name('settings');
                Route::patch('ayarlar', [MailClientController::class, 'updateSettings'])->name('settings.update');
                Route::get('test', [MailClientController::class, 'test'])->name('test');
                Route::post('test', [MailClientController::class, 'sendTest'])->name('test.send');
            });
        });

        Route::prefix('dosya-yoneticisi')->name('eys.filemanager.')->middleware('permission:eys.file_manager.view')->group(function () {
            Route::get('/', [FileManagerController::class, 'index'])->name('index');
            Route::get('api/browse', [FileManagerController::class, 'browse'])->name('browse');
            Route::get('api/thumb', [FileManagerController::class, 'thumb'])->name('thumb');
            Route::get('api/download', [FileManagerController::class, 'download'])->name('download');

            Route::middleware('permission:eys.file_manager.create')->group(function () {
                Route::post('api/upload', [FileManagerController::class, 'upload'])->name('upload');
                Route::post('api/folder', [FileManagerController::class, 'createFolder'])->name('folder');
            });

            Route::middleware('permission:eys.file_manager.manage')->group(function () {
                Route::post('api/rename', [FileManagerController::class, 'rename'])->name('rename');
                Route::post('api/move', [FileManagerController::class, 'move'])->name('move');
            });

            Route::middleware('permission:eys.file_manager.delete')->group(function () {
                Route::post('api/delete', [FileManagerController::class, 'delete'])->name('delete');
                Route::post('api/bulk-delete', [FileManagerController::class, 'bulkDelete'])->name('bulkDelete');
            });
        });
    });
});
