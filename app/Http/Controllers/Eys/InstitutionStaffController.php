<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — belirli bir kurumun yetkilileri (InstitutionStaff).
 * Kurumun kendi self-servis portalındaki StaffController ile aynı alan
 * setini kullanır, farkı: burada herhangi bir kurum EYS admini tarafından
 * yönetilebilir.
 */
class InstitutionStaffController extends Controller
{
    public function index(Request $request, Institution $institution): View
    {
        $staff = $institution->staff()
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->where(fn ($w) => $w->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('email', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.institutions.staff.index', [
            'institution' => $institution,
            'staff' => $staff,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(Institution $institution): View
    {
        return view('eys.institutions.staff.create', ['institution' => $institution]);
    }

    public function store(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.InstitutionStaff::class],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $staff = $institution->staff()->create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ]);

        // Bkz. institution.StaffController — EYS admini tarafından oluşturulan
        // yetkili için ayrıca e-posta doğrulaması istenmiyor.
        $staff->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('eys.institution-staff.index', $institution)->with('status', __('eys.institution_staff.created'));
    }

    public function edit(Institution $institution, InstitutionStaff $staff): View
    {
        $this->authorizeSameInstitution($institution, $staff);

        return view('eys.institutions.staff.edit', ['institution' => $institution, 'staff' => $staff]);
    }

    public function update(Request $request, Institution $institution, InstitutionStaff $staff): RedirectResponse
    {
        $this->authorizeSameInstitution($institution, $staff);

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(InstitutionStaff::class)->ignore($staff->id)],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'boolean'],
        ]);

        $staff->update($validated);

        return redirect()->route('eys.institution-staff.index', $institution)->with('status', __('eys.institution_staff.updated'));
    }

    public function destroy(Institution $institution, InstitutionStaff $staff): RedirectResponse
    {
        $this->authorizeSameInstitution($institution, $staff);

        $staff->delete();

        return redirect()->route('eys.institution-staff.index', $institution)->with('status', __('eys.institution_staff.deleted'));
    }

    private function authorizeSameInstitution(Institution $institution, InstitutionStaff $staff): void
    {
        abort_unless($staff->institution_id === $institution->id, 404);
    }
}
