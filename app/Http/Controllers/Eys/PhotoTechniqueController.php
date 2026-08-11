<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\PhotoTechnique;
use App\Models\PhotoTechniqueTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Fotoğraf Çekim Tekniği (referans veri) yönetimi.
 *
 * Çok dilli içerik: photo_techniques (sıra, durum) + photo_technique_translations
 * (ad). Form her dil için ayrı sekme sunar (bkz. Fotoğraf Kategorisi ile aynı desen).
 */
class PhotoTechniqueController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $photoTechniques = PhotoTechnique::with('translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                PhotoTechniqueTranslation::select('name')
                    ->whereColumn('photo_technique_id', 'photo_techniques.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.photo-techniques.index', [
            'photoTechniques' => $photoTechniques,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.photo-techniques.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $photoTechnique = PhotoTechnique::create([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $photoTechnique->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.photo-techniques.index')->with('status', __('eys.photo_technique.created'));
    }

    public function edit(PhotoTechnique $photoTechnique): View
    {
        $photoTechnique->load('translations');

        return view('eys.photo-techniques.edit', [
            'photoTechnique' => $photoTechnique,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, PhotoTechnique $photoTechnique): RedirectResponse
    {
        $data = $this->validateData($request);

        $photoTechnique->update([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $photoTechnique->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.photo-techniques.index')->with('status', __('eys.photo_technique.updated'));
    }

    public function destroy(PhotoTechnique $photoTechnique): RedirectResponse
    {
        $photoTechnique->delete();

        return redirect()->route('eys.photo-techniques.index')->with('status', __('eys.photo_technique.deleted'));
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
