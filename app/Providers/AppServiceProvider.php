<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Resend\SvixSignatureVerifier;
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

        $this->app->singleton(SvixSignatureVerifier::class, fn () => new SvixSignatureVerifier(
            config('services.resend.webhook_secret')
        ));
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
        //
        // NOT: `Authenticate` (auth middleware) tarafının eşdeğer düzeltmesi
        // burada DEĞİL, bootstrap/app.php'deki withMiddleware() içinde
        // ($middleware->redirectGuestsTo(...)) — bkz. o dosyadaki docblock.
        // Framework, ApplicationBuilder::withMiddleware() içinde HttpKernel
        // resolve edildiğinde otomatik olarak `Authenticate::redirectUsing(fn
        // () => route('login'))` varsayılanını kuruyor; bu resolve, bazı
        // çalışma bağlamlarında (tinker, test ortamı) bu provider'ın boot()
        // metodundan SONRA gerçekleşebiliyor ve burada set edilecek bir
        // callback'i sessizce ezebiliyor. `redirectGuestsTo()` ise doğrudan
        // aynı senkron closure içinde, varsayılandan hemen sonra çalıştığı
        // için bu yarış durumuna karşı bağışık.
        RedirectIfAuthenticated::redirectUsing(fn () => match (true) {
            Auth::guard('institution')->check() => route('institution.dashboard'),
            Auth::guard('temsilci')->check() => route('temsilci.dashboard'),
            Auth::guard('juri')->check() => route('juri.dashboard'),
            Auth::guard('eys')->check() => route('eys.dashboard'),
            default => route('dashboard'),
        });

        // App\Listeners\LogSentMail zaten burada elle kaydedilmiyor —
        // Laravel'in olay otomatik keşfi, `handle(MessageSent $event)`
        // imzasından onu kendiliğinden bağlıyor (bkz. app/Listeners/LogSentMail.php).
        // Elle bir de Event::listen() eklemek, her gönderimi İKİ KEZ
        // loglamaya yol açıyordu — bu düzeltmenin sebebi buydu.
    }
}
