<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Dil değiştirme — guard/subdomain'den bağımsız, paylaşılan tek controller.
 * Her grubun session'ı izole olduğundan (bkz. ResolveGuardSessionCookie),
 * seçim yalnızca o anki portala kaydedilir.
 */
class SetLanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (array_key_exists($locale, config('locales.supported'))) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->back();
    }
}
