<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kurum/Temsilci/Juri/Uye — her biri kendi session cookie ismini alır.
 * Bu bilinçli bir izolasyon: subdomain'lerin doğal host-only cookie
 * scoping'ine güvenmek yerine, her grup için ayrı bir cookie ismi açıkça
 * tanımlanıyor (bkz. docs/MEVCUT-YAPI-ANALIZ.md §9 ve proje planı).
 *
 * KRİTİK: bu middleware `bootstrap/app.php`'de `web(prepend: [...])` ile,
 * yani StartSession çalışmadan ÖNCE tetiklenecek şekilde eklenmeli.
 * `config('session.cookie')` StartSession tarafından bir kez okunuyor —
 * bunu bir route-group middleware'inden (global `web` stack'inden SONRA
 * çalışır) değiştirmeye çalışmak sessizce işe yaramaz.
 */
class ResolveGuardSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieNameByHost = [
            config('domains.institution') => 'institution_session',
            config('domains.temsilci') => 'temsilci_session',
            config('domains.juri') => 'juri_session',
            config('domains.uye') => 'uye_session',
        ];

        if ($cookieName = $cookieNameByHost[$request->getHost()] ?? null) {
            config(['session.cookie' => $cookieName]);
        }

        return $next($request);
    }
}
