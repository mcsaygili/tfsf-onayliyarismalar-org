<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kurum kaydı sadece e-posta+şifre ile oluşturulduğu için (bkz.
 * RegisteredController), kurum adı/adres bilgileri ve yetkilinin ad/soyad
 * bilgileri burada, girişten sonra tamamlanıyor.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('institution.profile.edit', [
            'staff' => Auth::guard('institution')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_email' => ['nullable', 'string', 'email', 'max:255'],
            'institution_phone' => ['nullable', 'string', 'max:50'],
            'institution_website' => ['nullable', 'string', 'url', 'max:255'],
            'institution_address' => ['nullable', 'string', 'max:1000'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $staff = Auth::guard('institution')->user();

        DB::transaction(function () use ($staff, $validated) {
            $staff->institution->update([
                'name' => $validated['institution_name'],
                'email' => $validated['institution_email'],
                'phone' => $validated['institution_phone'],
                'website' => $validated['institution_website'],
                'address' => $validated['institution_address'],
            ]);

            $staff->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
            ]);
        });

        return redirect()->route('institution.profile.edit')->with('status', __('institution.profile.updated'));
    }
}
