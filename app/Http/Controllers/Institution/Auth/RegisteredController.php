<?php

namespace App\Http\Controllers\Institution\Auth;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kurum kaydı — sadece e-posta + şifre alır. Kurum adı, adres vb. ile
 * personelin ad/soyad bilgileri kayıttan SONRA, giriş yapıp e-postasını
 * doğruladıktan sonra ayrı bir ekrandan doldurulacak (bkz. proje notu).
 *
 * Bu fazda tüm kayıtlar otomatik onaylı sayılıyor (Institution::approved_at
 * kayıt anında set edilir) — ileride TFSF admin onayı gerektiren gerçek
 * bir işleme dönüşecek.
 */
class RegisteredController extends Controller
{
    public function create(): View
    {
        return view('institution.auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.InstitutionStaff::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $staff = DB::transaction(function () use ($validated) {
            $institution = Institution::create([
                'status' => true,
                'approved_at' => now(),
            ]);

            return InstitutionStaff::create([
                'institution_id' => $institution->id,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        });

        event(new Registered($staff));

        return redirect()->route('institution.login')
            ->with('status', __('institution.register.check_email'));
    }
}
