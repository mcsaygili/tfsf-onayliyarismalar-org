<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel'in yerleşik `verified` middleware'i (Illuminate\Auth\Middleware\
 * EnsureEmailIsVerified) her zaman `$request->user()` — yani VARSAYILAN
 * guard'ı ('web') — kontrol ediyor, guard parametresi kabul etmiyor.
 * Bizim 4 guard'lı mimarimizde (institution/temsilci/juri/web) bu işe
 * yaramıyor; bu yüzden guard'ı açıkça parametre olarak alan bir eşdeğeri.
 *
 * Kullanım: `verified.guard:institution` veya özel yönlendirme rotasıyla
 * `verified.guard:institution,institution.verification.notice`.
 */
class EnsureGuardEmailIsVerified
{
    public function handle(Request $request, Closure $next, string $guard, ?string $redirectToRoute = null): Response
    {
        $user = Auth::guard($guard)->user();

        if (! $user || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route($redirectToRoute ?: $guard.'.verification.notice'));
        }

        return $next($request);
    }
}
