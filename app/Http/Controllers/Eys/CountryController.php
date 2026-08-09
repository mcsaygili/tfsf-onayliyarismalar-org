<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Ülke (referans veri) yönetimi.
 *
 * Çok dilli içerik: country (ISO kodları, durum) + country_translations
 * (resmi ad / kısa ad / uyruk). Form her dil için ayrı sekme sunar.
 */
class CountryController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $countries = Country::with('translations')
            ->withCount('cities')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('official_name', 'like', "%{$name}%")
                    ->orWhere('short_name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            // Aktif dildeki resmi ada göre sırala.
            ->orderBy(
                CountryTranslation::select('official_name')
                    ->whereColumn('country_id', 'countries.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.countries.index', [
            'countries' => $countries,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.countries.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $country = Country::create([
            'iso_alpha2' => $this->cleanIso($data['iso_alpha2'] ?? null, 2),
            'iso_alpha3' => $this->cleanIso($data['iso_alpha3'] ?? null, 3),
            'status' => (bool) $data['status'],
        ]);

        $country->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.countries.index')->with('status', __('eys.country.created'));
    }

    public function edit(Country $country): View
    {
        $country->load('translations');

        return view('eys.countries.edit', [
            'country' => $country,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $data = $this->validateData($request);

        $country->update([
            'iso_alpha2' => $this->cleanIso($data['iso_alpha2'] ?? null, 2),
            'iso_alpha3' => $this->cleanIso($data['iso_alpha3'] ?? null, 3),
            'status' => (bool) $data['status'],
        ]);

        $country->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.countries.index')->with('status', __('eys.country.updated'));
    }

    public function destroy(Country $country): RedirectResponse
    {
        if ($country->cities()->exists()) {
            return redirect()->route('eys.countries.index')->with('status', __('eys.country.has_cities'));
        }

        $country->delete();

        return redirect()->route('eys.countries.index')->with('status', __('eys.country.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');

        $rules = [
            'status' => ['required', 'in:0,1'],
            'iso_alpha2' => ['nullable', 'string', 'max:2'],
            'iso_alpha3' => ['nullable', 'string', 'max:3'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.official_name"] = [
                $locale === $defaultLocale ? 'required' : 'nullable',
                'string', 'max:255',
            ];
            $rules["{$locale}.short_name"] = ['nullable', 'string', 'max:255'];
            $rules["{$locale}.nationality"] = ['nullable', 'string', 'max:255'];
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
            $official = $data[$locale]['official_name'] ?? null;

            if (blank($official)) {
                continue;
            }

            $payload[$locale] = [
                'official_name' => $official,
                'short_name' => $data[$locale]['short_name'] ?? null,
                'nationality' => $data[$locale]['nationality'] ?? null,
            ];
        }

        return $payload;
    }

    private function cleanIso(?string $value, int $length): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : substr($value, 0, $length);
    }
}
