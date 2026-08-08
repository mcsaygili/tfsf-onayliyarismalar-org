<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aktif dili her istekte belirler.
 *
 * Öncelik: oturumdaki 'locale' -> tarayıcı Accept-Language -> config varsayılan.
 * Her grubun (uye/institution/temsilci/juri) kendi izole session cookie'si
 * olduğundan (bkz. ResolveGuardSessionCookie), dil tercihi de o portala özel
 * hatırlanır — bilinçli olarak subdomain'ler arasında paylaşılmaz.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('locales.supported'));
        $default = config('locales.default', config('app.locale'));

        $locale = $request->session()->get('locale');

        if (! in_array($locale, $supported, true)) {
            $locale = $request->getPreferredLanguage($supported) ?: $default;
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
