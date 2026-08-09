<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Kurum kaydı sadece e-posta+şifre ile oluşturulduğu için (bkz.
 * RegisteredController), kurum adı/adres bilgileri girişten sonra burada
 * tamamlanıyor. Yetkili (personel) kayıtları StaffController üzerinden
 * ayrıca yönetiliyor — bir kuruma birden fazla yetkili bağlanabiliyor.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('institution.profile.edit', [
            'institution' => Auth::guard('institution')->user()->institution,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_email' => ['required', 'string', 'email', 'max:255'],
            'institution_phone' => ['required', 'string', 'max:50'],
            'institution_website' => ['nullable', 'string', 'url', 'max:255'],
            'institution_address' => ['nullable', 'string', 'max:1000'],
        ]);

        Auth::guard('institution')->user()->institution->update([
            'name' => $validated['institution_name'],
            'email' => $validated['institution_email'],
            'phone' => $validated['institution_phone'],
            'website' => $validated['institution_website'],
            'address' => $validated['institution_address'],
        ]);

        return redirect()->route('institution.profile.edit')->with('status', __('institution.profile.updated'));
    }
}
