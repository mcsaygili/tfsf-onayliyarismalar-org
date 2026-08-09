<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use App\Models\InstitutionStaff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Bir kuruma birden fazla yetkili (InstitutionStaff) bağlanabilir — bu
 * controller, kurumun kendi personel listesini yönetmesini sağlıyor.
 * Kayıt sırasında sadece 1 yetkili oluşuyordu (bkz. Auth\RegisteredController);
 * ek yetkililer buradan, oturum açmış bir yetkili tarafından ekleniyor.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.staff.index', [
            'staffList' => $institution->staff()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('institution.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.InstitutionStaff::class],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $institution = Auth::guard('institution')->user()->institution;

        $newStaff = $institution->staff()->create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        // `email_verified_at` kasıtlı olarak mass-fillable değil (bkz.
        // InstitutionStaff#[Fillable]) — bu kayıt, kurumun zaten doğrulanmış
        // bir yetkilisi tarafından panel içinden oluşturuluyor, bu yüzden
        // genel kayıt akışındaki (bkz. Auth\RegisteredController) e-posta
        // doğrulaması burada gerekmiyor.
        $newStaff->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('institution.staff.index')->with('status', __('institution.staff.created'));
    }

    public function edit(InstitutionStaff $staff): View
    {
        $this->authorizeSameInstitution($staff);

        return view('institution.staff.edit', ['staff' => $staff]);
    }

    public function update(Request $request, InstitutionStaff $staff): RedirectResponse
    {
        $this->authorizeSameInstitution($staff);

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(InstitutionStaff::class)->ignore($staff->id)],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'boolean'],
        ]);

        $staff->update($validated);

        return redirect()->route('institution.staff.index')->with('status', __('institution.staff.updated'));
    }

    private function authorizeSameInstitution(InstitutionStaff $staff): void
    {
        if ($staff->institution_id !== Auth::guard('institution')->user()->institution_id) {
            throw new AuthorizationException;
        }
    }
}
