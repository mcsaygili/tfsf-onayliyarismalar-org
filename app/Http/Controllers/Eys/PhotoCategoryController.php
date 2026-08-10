<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\PhotoCategory;
use App\Models\PhotoCategoryTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Fotoğraf Kategorisi (referans veri) yönetimi.
 *
 * Çok dilli içerik: photo_categories (sıra, durum) + photo_category_translations
 * (ad). Form her dil için ayrı sekme sunar (bkz. Öğrenim Durumu ile aynı desen).
 */
class PhotoCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $photoCategories = PhotoCategory::with('translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                PhotoCategoryTranslation::select('name')
                    ->whereColumn('photo_category_id', 'photo_categories.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.photo-categories.index', [
            'photoCategories' => $photoCategories,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.photo-categories.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $photoCategory = PhotoCategory::create([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $photoCategory->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.photo-categories.index')->with('status', __('eys.photo_category.created'));
    }

    public function edit(PhotoCategory $photoCategory): View
    {
        $photoCategory->load('translations');

        return view('eys.photo-categories.edit', [
            'photoCategory' => $photoCategory,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, PhotoCategory $photoCategory): RedirectResponse
    {
        $data = $this->validateData($request);

        $photoCategory->update([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $photoCategory->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.photo-categories.index')->with('status', __('eys.photo_category.updated'));
    }

    public function destroy(PhotoCategory $photoCategory): RedirectResponse
    {
        $photoCategory->delete();

        return redirect()->route('eys.photo-categories.index')->with('status', __('eys.photo_category.deleted'));
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
