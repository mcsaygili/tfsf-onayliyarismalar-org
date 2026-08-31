<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\MemberRestriction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Üye (guard: web, App\Models\User) yönetimi. Sadece
 * temel profil alanları yönetilir; self-servis profildeki adres/doğum
 * tarihi/ülke-şehir gibi kişisel detaylar bu ilk sürümün kapsamı dışında.
 */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = User::query()
            ->with('educationLevel.translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->where(fn ($w) => $w->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('email', 'like', "%{$name}%")
                    ->orWhere('username', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1', '90'], true),
                fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.uyeler.index', [
            'members' => $members,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.uyeler.create', [
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request, null);

        $member = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'uye_turu' => $validated['uye_turu'],
            'status' => $validated['status'],
        ]);

        $member->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('eys.uyeler.index')->with('status', __('eys.uye.created'));
    }

    public function edit(User $uye): View
    {
        $uye->load([
            'restrictions.creator', 'restrictions.lifter',
            'competitionEntries' => fn ($query) => $query->with(['competition.translations', 'submissions.photos'])->latest()->limit(20),
        ]);

        return view('eys.uyeler.edit', [
            'member' => $uye,
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function restrict(Request $request, User $uye): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['competition', 'account'])],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $uye->restrictions()->create($validated + ['created_by' => Auth::guard('eys')->id()]);

        return back()->with('status', 'Üye kısıtlaması kaydedildi.');
    }

    public function liftRestriction(Request $request, User $uye, MemberRestriction $restriction): RedirectResponse
    {
        abort_unless($restriction->user_id === $uye->id && $restriction->lifted_at === null, 404);
        $validated = $request->validate(['lift_reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $restriction->update($validated + ['lifted_at' => now(), 'lifted_by' => Auth::guard('eys')->id()]);

        return back()->with('status', 'Üye kısıtlaması kaldırıldı.');
    }

    public function update(Request $request, User $uye): RedirectResponse
    {
        $validated = $this->validateData($request, $uye);

        $uye->update([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'uye_turu' => $validated['uye_turu'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('eys.uyeler.index')->with('status', __('eys.uye.updated'));
    }

    public function destroy(User $uye): RedirectResponse
    {
        $uye->delete();

        return redirect()->route('eys.uyeler.index')->with('status', __('eys.uye.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?User $member): array
    {
        $rules = [
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($member?->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($member?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'uye_turu' => ['required', 'integer', 'in:0,1,2,3'],
            'status' => ['required', 'integer', 'in:0,1,90'],
        ];

        if (! $member) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $request->validate($rules);
    }
}
