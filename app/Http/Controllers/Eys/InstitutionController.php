<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Kurum (Institution) yönetimi. Kurumun kendi
 * yetkilileri (InstitutionStaff) ayrı bir nested kaynak olarak
 * InstitutionStaffController'da yönetiliyor (bkz. /kurumlar/{institution}/yetkililer).
 */
class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $institutions = Institution::query()
            ->withCount('staff')
            ->with('institutionType.translations')
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.institutions.index', [
            'institutions' => $institutions,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.institutions.create', [
            'institutionTypes' => InstitutionType::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);

        Institution::create($validated + ['approved_at' => now()]);

        return redirect()->route('eys.institutions.index')->with('status', __('eys.institution.created'));
    }

    public function edit(Institution $institution): View
    {
        return view('eys.institutions.edit', [
            'institution' => $institution,
            'institutionTypes' => InstitutionType::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $this->validateData($request);

        $institution->update($validated);

        return redirect()->route('eys.institutions.index')->with('status', __('eys.institution.updated'));
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        if ($institution->staff()->exists()) {
            return redirect()->route('eys.institutions.index')->with('status', __('eys.institution.has_staff'));
        }

        $institution->delete();

        return redirect()->route('eys.institutions.index')->with('status', __('eys.institution.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'institution_type_id' => ['nullable', 'uuid', 'exists:institution_types,id'],
            'status' => ['required', 'boolean'],
        ]);
    }
}
