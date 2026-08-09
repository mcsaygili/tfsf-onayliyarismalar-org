<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Kurum Türü (referans veri) yönetimi.
 *
 * Çok dilli içerik: institution_types (sıra, durum) + institution_type_translations
 * (ad). Form her dil için ayrı sekme sunar (bkz. Ülke/Şehir/Öğrenim Durumu ile aynı desen).
 */
class InstitutionTypeController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $institutionTypes = InstitutionType::with('translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                InstitutionTypeTranslation::select('name')
                    ->whereColumn('institution_type_id', 'institution_types.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.institution-types.index', [
            'institutionTypes' => $institutionTypes,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.institution-types.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $institutionType = InstitutionType::create([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $institutionType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.institution-types.index')->with('status', __('eys.institution_type.created'));
    }

    public function edit(InstitutionType $institutionType): View
    {
        $institutionType->load('translations');

        return view('eys.institution-types.edit', [
            'institutionType' => $institutionType,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, InstitutionType $institutionType): RedirectResponse
    {
        $data = $this->validateData($request);

        $institutionType->update([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $institutionType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.institution-types.index')->with('status', __('eys.institution_type.updated'));
    }

    public function destroy(InstitutionType $institutionType): RedirectResponse
    {
        $institutionType->delete();

        return redirect()->route('eys.institution-types.index')->with('status', __('eys.institution_type.deleted'));
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
