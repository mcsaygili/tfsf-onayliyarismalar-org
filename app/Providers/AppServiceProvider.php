<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\NetgsmSmsSender;
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
        //
    }
}
