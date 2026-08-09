<?php

use App\Http\Middleware\EnsureGuardEmailIsVerified;
use App\Http\Middleware\ResolveGuardSessionCookie;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeam;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Session/çerezler başlamadan ÖNCE çalışmalı — bkz. ResolveGuardSessionCookie
        // doc bloğu (StartSession, config('session.cookie')'yu bir kez okur).
        $middleware->web(prepend: [
            ResolveGuardSessionCookie::class,
        ]);

        // Session başladıktan SONRA çalışmalı (session'dan locale okur/yazar) —
        // bu yüzden append, prepend değil.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'verified.guard' => EnsureGuardEmailIsVerified::class,
            // EYS Roller/İzinler (Spatie laravel-permission, teams = App\Enums\Module).
            'team' => SetPermissionsTeam::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Laravel'in yerleşik `auth` middleware'i (Authenticate), oturum
        // açmamış bir kullanıcıyı korumalı bir sayfadan uzaklaştırırken
        // varsayılan olarak bare `login` route'unu arıyor — guard'dan
        // bağımsız. Bu, ör. `kurum.*` subdomain'inde oturumu olmayan biri
        // `auth:institution` korumalı bir sayfaya gittiğinde yanlışlıkla
        // Üye'nin (kök domain) login sayfasına düşmesine yol açıyordu
        // (bkz. AppServiceProvider'daki RedirectIfAuthenticated
        // düzeltmesinin "ters" hâli — aynı sınıf hata). BURADA, tam olarak
        // framework'ün `route('login')` varsayılanını kurduğu yerde
        // (ApplicationBuilder::withMiddleware()) override ediyoruz — bu
        // varsayılan, HttpKernel resolve edildiğinde otomatik kuruluyor ve
        // bir ServiceProvider::boot()'tan set edilen bir callback'i bazı
        // çalışma bağlamlarında (tinker, test ortamı) sessizce ezebiliyor.
        $middleware->redirectGuestsTo(fn (Request $request) => match ($request->getHost()) {
            config('domains.institution') => route('institution.login'),
            config('domains.temsilci') => route('temsilci.login'),
            config('domains.juri') => route('juri.login'),
            config('domains.eys') => route('eys.login'),
            default => route('login'),
        });

        // Resend'in webhook isteği bir tarayıcı formu değil, sunucudan
        // sunucuya bir POST — CSRF token taşımıyor. Kimlik doğrulaması
        // yerine Svix imza kontrolü kullanılıyor (bkz. ResendEventWebhookController).
        $middleware->validateCsrfTokens(except: [
            'webhooks/resend',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // `shouldRenderJsonWhen()` bir varsayılanı EKLEMEK değil, TAMAMEN
        // DEĞİŞTİRMEK için var — bu yüzden `$request->expectsJson()` burada
        // da kontrol edilmeli. Aksi halde (bkz. Dosya Yöneticisi/Resend
        // webhook JSON API'leri) bir doğrulama hatası, JSON isteyen bir
        // fetch() çağrısına bile 422 JSON yerine HTML yönlendirme sayfası
        // döndürür — istemci tarafı sessizce bozulur.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
