<?php

use App\Http\Controllers\Eys\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Eys\Auth\NewPasswordController;
use App\Http\Controllers\Eys\Auth\PasswordResetLinkController;
use App\Http\Controllers\Eys\CityController;
use App\Http\Controllers\Eys\CountryController;
use App\Http\Controllers\Eys\DashboardController;
use App\Http\Controllers\Eys\EducationLevelController;
use App\Http\Controllers\Eys\FileManagerController;
use App\Http\Controllers\Eys\InstitutionController;
use App\Http\Controllers\Eys\InstitutionStaffController;
use App\Http\Controllers\Eys\InstitutionTypeController;
use App\Http\Controllers\Eys\JuriController;
use App\Http\Controllers\Eys\MailClientController;
use App\Http\Controllers\Eys\MemberController;
use App\Http\Controllers\Eys\ModuleDashboardController;
use App\Http\Controllers\Eys\PermissionController;
use App\Http\Controllers\Eys\RegulationItemController;
use App\Http\Controllers\Eys\RegulationSectionController;
use App\Http\Controllers\Eys\RoleController;
use App\Http\Controllers\Eys\TemsilciController;
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

        Route::prefix('sartname-bolumleri')->name('eys.regulation-sections.')->middleware('permission:eys.regulation_sections.manage')->group(function () {
            Route::get('/', [RegulationSectionController::class, 'index'])->name('index');
            Route::get('yeni', [RegulationSectionController::class, 'create'])->name('create');
            Route::post('/', [RegulationSectionController::class, 'store'])->name('store');
            Route::get('{regulationSection}/duzenle', [RegulationSectionController::class, 'edit'])->name('edit');
            Route::patch('{regulationSection}', [RegulationSectionController::class, 'update'])->name('update');
            Route::delete('{regulationSection}', [RegulationSectionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('sartname-maddeleri')->name('eys.regulation-items.')->middleware('permission:eys.regulation_items.manage')->group(function () {
            Route::get('/', [RegulationItemController::class, 'index'])->name('index');
            Route::get('yeni', [RegulationItemController::class, 'create'])->name('create');
            Route::post('/', [RegulationItemController::class, 'store'])->name('store');
            Route::get('{regulationItem}/duzenle', [RegulationItemController::class, 'edit'])->name('edit');
            Route::patch('{regulationItem}', [RegulationItemController::class, 'update'])->name('update');
            Route::delete('{regulationItem}', [RegulationItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('kurum')->name('eys.institution.')->middleware(['team:Institution', 'permission:institution.dashboard.view'])->group(function () {
            Route::get('/', [ModuleDashboardController::class, 'institution'])->name('dashboard');
        });

        Route::prefix('temsilci')->name('eys.temsilci.')->middleware(['team:Temsilci', 'permission:representative.dashboard.view'])->group(function () {
            Route::get('/', [ModuleDashboardController::class, 'temsilci'])->name('dashboard');
        });

        Route::prefix('juri')->name('eys.juri.')->middleware(['team:Juri', 'permission:jury.dashboard.view'])->group(function () {
            Route::get('/', [ModuleDashboardController::class, 'juri'])->name('dashboard');
        });

        Route::prefix('uye')->name('eys.uye.')->middleware(['team:Uye', 'permission:member.dashboard.view'])->group(function () {
            Route::get('/', [ModuleDashboardController::class, 'uye'])->name('dashboard');
        });

        Route::prefix('kurumlar')->name('eys.institutions.')->middleware(['team:Institution', 'permission:institution.institutions.manage'])->group(function () {
            Route::get('/', [InstitutionController::class, 'index'])->name('index');
            Route::get('yeni', [InstitutionController::class, 'create'])->name('create');
            Route::post('/', [InstitutionController::class, 'store'])->name('store');
            Route::get('{institution}/duzenle', [InstitutionController::class, 'edit'])->name('edit');
            Route::patch('{institution}', [InstitutionController::class, 'update'])->name('update');
            Route::delete('{institution}', [InstitutionController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('kurumlar/{institution}/yetkililer')->name('eys.institution-staff.')->middleware(['team:Institution', 'permission:institution.institution_staff.manage'])->group(function () {
            Route::get('/', [InstitutionStaffController::class, 'index'])->name('index');
            Route::get('yeni', [InstitutionStaffController::class, 'create'])->name('create');
            Route::post('/', [InstitutionStaffController::class, 'store'])->name('store');
            Route::get('{staff}/duzenle', [InstitutionStaffController::class, 'edit'])->name('edit');
            Route::patch('{staff}', [InstitutionStaffController::class, 'update'])->name('update');
            Route::delete('{staff}', [InstitutionStaffController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('temsilciler')->name('eys.temsilciler.')->middleware(['team:Temsilci', 'permission:representative.representatives.manage'])->group(function () {
            Route::get('/', [TemsilciController::class, 'index'])->name('index');
            Route::get('yeni', [TemsilciController::class, 'create'])->name('create');
            Route::post('/', [TemsilciController::class, 'store'])->name('store');
            Route::get('{temsilci}/duzenle', [TemsilciController::class, 'edit'])->name('edit');
            Route::patch('{temsilci}', [TemsilciController::class, 'update'])->name('update');
            Route::delete('{temsilci}', [TemsilciController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('juriler')->name('eys.juriler.')->middleware(['team:Juri', 'permission:jury.jurors.manage'])->group(function () {
            Route::get('/', [JuriController::class, 'index'])->name('index');
            Route::get('yeni', [JuriController::class, 'create'])->name('create');
            Route::post('/', [JuriController::class, 'store'])->name('store');
            Route::get('{juri}/duzenle', [JuriController::class, 'edit'])->name('edit');
            Route::patch('{juri}', [JuriController::class, 'update'])->name('update');
            Route::delete('{juri}', [JuriController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('uyeler')->name('eys.uyeler.')->middleware(['team:Uye', 'permission:member.members.manage'])->group(function () {
            Route::get('/', [MemberController::class, 'index'])->name('index');
            Route::get('yeni', [MemberController::class, 'create'])->name('create');
            Route::post('/', [MemberController::class, 'store'])->name('store');
            Route::get('{uye}/duzenle', [MemberController::class, 'edit'])->name('edit');
            Route::patch('{uye}', [MemberController::class, 'update'])->name('update');
            Route::delete('{uye}', [MemberController::class, 'destroy'])->name('destroy');
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
