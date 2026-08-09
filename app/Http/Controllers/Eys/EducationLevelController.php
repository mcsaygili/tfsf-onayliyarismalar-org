<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use App\Models\EducationLevelTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Öğrenim Durumu (referans veri) yönetimi.
 *
 * Çok dilli içerik: education_levels (sıra, durum) + education_level_translations
 * (ad). Form her dil için ayrı sekme sunar (bkz. Ülke/Şehir ile aynı desen).
 */
class EducationLevelController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $educationLevels = EducationLevel::with('translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                EducationLevelTranslation::select('name')
                    ->whereColumn('education_level_id', 'education_levels.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.education-levels.index', [
            'educationLevels' => $educationLevels,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.education-levels.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $educationLevel = EducationLevel::create([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $educationLevel->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.education-levels.index')->with('status', __('eys.education_level.created'));
    }

    public function edit(EducationLevel $educationLevel): View
    {
        $educationLevel->load('translations');

        return view('eys.education-levels.edit', [
            'educationLevel' => $educationLevel,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, EducationLevel $educationLevel): RedirectResponse
    {
        $data = $this->validateData($request);

        $educationLevel->update([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $educationLevel->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.education-levels.index')->with('status', __('eys.education_level.updated'));
    }

    public function destroy(EducationLevel $educationLevel): RedirectResponse
    {
        $educationLevel->delete();

        return redirect()->route('eys.education-levels.index')->with('status', __('eys.education_level.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');

        $rules = [
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.name"] = [
                $locale === $defaultLocale ? 'required' : 'nullable',
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
