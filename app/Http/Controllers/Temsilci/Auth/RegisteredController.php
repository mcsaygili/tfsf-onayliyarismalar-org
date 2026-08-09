<?php

namespace App\Http\Controllers\Temsilci\Auth;

use App\Http\Controllers\Controller;
use App\Models\Temsilci;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Temsilci kaydı — Institution'daki gibi sadece e-posta + şifre alır.
 * Ad/soyad ve telefon, giriş yapıp e-postasını doğruladıktan sonra
 * Temsilci Bilgileri sayfasından tamamlanır.
 */
class RegisteredController extends Controller
{
    public function create(): View
    {
        return view('temsilci.auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Temsilci::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $temsilci = Temsilci::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($temsilci));

        return redirect()->route('temsilci.login')
            ->with('status', __('temsilci.register.check_email'));
    }
}
