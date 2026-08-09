<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\Juri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/** EYS yönetici paneli — Jüri (guard: juri) yönetimi. */
class JuriController extends Controller
{
    public function index(Request $request): View
    {
        $juriler = Juri::query()
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

        return view('eys.juriler.index', [
            'juriler' => $juriler,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.juriler.create', [
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Juri::class],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tckimlikno' => ['nullable', 'string', 'size:11'],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'status' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $juri = Juri::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'tckimlikno' => $validated['tckimlikno'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $juri->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('eys.juriler.index')->with('status', __('eys.juri.created'));
    }

    public function edit(Juri $juri): View
    {
        return view('eys.juriler.edit', [
            'juri' => $juri,
            'educationLevels' => EducationLevel::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function update(Request $request, Juri $juri): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Juri::class)->ignore($juri->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tckimlikno' => ['nullable', 'string', 'size:11'],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'status' => ['required', 'boolean'],
        ]);

        $juri->update($validated);

        return redirect()->route('eys.juriler.index')->with('status', __('eys.juri.updated'));
    }

    public function destroy(Juri $juri): RedirectResponse
    {
        $juri->delete();

        return redirect()->route('eys.juriler.index')->with('status', __('eys.juri.deleted'));
    }
}
