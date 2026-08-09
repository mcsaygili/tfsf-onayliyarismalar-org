<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\Temsilci;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/** EYS yönetici paneli — Temsilci (guard: temsilci) yönetimi. */
class TemsilciController extends Controller
{
    public function index(Request $request): View
    {
        $temsilciler = Temsilci::query()
            ->with('educationLevel.translations')
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

        return view('eys.temsilciler.index', [
            'temsilciler' => $temsilciler,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.temsilciler.create', [
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Temsilci::class],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'status' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $temsilci = Temsilci::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $temsilci->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('eys.temsilciler.index')->with('status', __('eys.temsilci.created'));
    }

    public function edit(Temsilci $temsilci): View
    {
        return view('eys.temsilciler.edit', [
            'temsilci' => $temsilci,
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function update(Request $request, Temsilci $temsilci): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Temsilci::class)->ignore($temsilci->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'status' => ['required', 'boolean'],
        ]);

        $temsilci->update($validated);

        return redirect()->route('eys.temsilciler.index')->with('status', __('eys.temsilci.updated'));
    }

    public function destroy(Temsilci $temsilci): RedirectResponse
    {
        $temsilci->delete();

        return redirect()->route('eys.temsilciler.index')->with('status', __('eys.temsilci.deleted'));
    }
}
