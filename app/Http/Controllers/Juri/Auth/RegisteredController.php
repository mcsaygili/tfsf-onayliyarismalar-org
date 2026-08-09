<?php

namespace App\Http\Controllers\Juri\Auth;

use App\Http\Controllers\Controller;
use App\Models\Juri;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Jüri kaydı — Institution'daki gibi sadece e-posta + şifre alır. Ad/soyad,
 * telefon ve T.C. kimlik no, giriş yapıp e-postasını doğruladıktan sonra
 * Juri Bilgileri sayfasından tamamlanır.
 */
class RegisteredController extends Controller
{
    public function create(): View
    {
        return view('juri.auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Juri::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $juri = Juri::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($juri));

        return redirect()->route('juri.login')
            ->with('status', __('juri.register.check_email'));
    }
}
