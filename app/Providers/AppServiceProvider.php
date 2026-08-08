<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\NetgsmSmsSender;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // SMS göndericisi — config'teki sürücüye göre (mock 'log' / gerçek 'netgsm').
        $this->app->singleton(SmsSender::class, function () {
            $cfg = config('services.sms');

            return match ($cfg['driver'] ?? 'log') {
                'netgsm' => new NetgsmSmsSender($cfg['netgsm'] ?? []),
                default => new LogSmsSender($cfg['from'] ?? 'TFSF'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel'in yerleşik `guest` middleware'i (RedirectIfAuthenticated),
        // zaten oturum açmış bir kullanıcıyı login/register gibi sayfalardan
        // uzaklaştırırken varsayılan olarak SADECE bare `dashboard` route'unu
        // arıyor (bkz. defaultRedirectUri()) — guard'dan bağımsız. Bizim 4
        // guard'lı mimarimizde bu, ör. institution guard'ında oturum açık bir
        // kullanıcı `guest:institution` korumalı bir sayfaya (login/register)
        // gittiğinde yanlışlıkla Üye'nin (web guard) dashboard/login akışına
        // düşmesine yol açıyordu (bkz. EnsureGuardEmailIsVerified'daki aynı
        // sınıf hatanın VerifyEmail eşdeğeri). Burada hangi guard'ın oturum
        // açık olduğuna göre doğru dashboard'a yönlendiriyoruz.
        RedirectIfAuthenticated::redirectUsing(fn () => match (true) {
            Auth::guard('institution')->check() => route('institution.dashboard'),
            Auth::guard('temsilci')->check() => route('temsilci.dashboard'),
            Auth::guard('juri')->check() => route('juri.dashboard'),
            default => route('dashboard'),
        });
    }
}
