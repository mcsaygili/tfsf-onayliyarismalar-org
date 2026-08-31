<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\RegulationSection;
use App\Models\RegulationSectionTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Şartname Bölümü (referans veri) yönetimi. Yarışma
 * Sistemi'nin şartname kütüphanesinin başlık katmanı — asıl dinamik
 * şartname üretim/tüketim motoru ayrı bir faz (bkz. proje planı).
 */
class RegulationSectionController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $sections = RegulationSection::with('translations')
            ->withCount('items')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                RegulationSectionTranslation::select('name')
                    ->whereColumn('regulation_section_id', 'regulation_sections.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.regulation-sections.index', [
            'sections' => $sections,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.regulation-sections.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $section = RegulationSection::create([
            'code' => $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $section->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.regulation-sections.index')->with('status', __('eys.regulation_section.created'));
    }

    public function edit(RegulationSection $regulationSection): View
    {
        $regulationSection->load('translations');

        return view('eys.regulation-sections.edit', [
            'section' => $regulationSection,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, RegulationSection $regulationSection): RedirectResponse
    {
        $data = $this->validateData($request);

        $regulationSection->update([
            'code' => $regulationSection->is_system ? $regulationSection->code : $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
            'version' => $regulationSection->version + 1,
        ]);

        $regulationSection->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.regulation-sections.index')->with('status', __('eys.regulation_section.updated'));
    }

    public function destroy(RegulationSection $regulationSection): RedirectResponse
    {
        if ($regulationSection->is_system || $regulationSection->items()->exists()) {
            return redirect()->route('eys.regulation-sections.index')->with('status', __('eys.regulation_section.has_items'));
        }

        $regulationSection->delete();

        return redirect()->route('eys.regulation-sections.index')->with('status', __('eys.regulation_section.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');

        $rules = [
            'code' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('regulation_sections', 'code')->ignore($request->route('regulationSection')?->id)],
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.name"] = [
                'required',
                'string', 'max:255',
            ];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationPayload(array $data): array
    {
        $payload = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $name = $data[$locale]['name'] ?? null;

            if (blank($name)) {
                continue;
            }

            $payload[$locale] = ['name' => $name];
        }

        return $payload;
    }
}
