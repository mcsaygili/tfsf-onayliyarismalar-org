<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityTranslation;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Şehir (referans veri) yönetimi.
 *
 * Şehir bir ülkeye bağlıdır; çok dilli resmi adı city_translations'ta tutulur.
 */
class CityController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $cities = City::with(['translations', 'country.translations'])
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('official_name', 'like', "%{$name}%"));
            })
            ->when($request->filled('country_id'),
                fn ($q) => $q->where('country_id', $request->input('country_id')))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy(
                CityTranslation::select('official_name')
                    ->whereColumn('city_id', 'cities.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.cities.index', [
            'cities' => $cities,
            'countryOptions' => $this->countryOptions(),
            'filter' => [
                'name' => $request->input('name', ''),
                'country_id' => $request->input('country_id', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.cities.create', [
            'locales' => array_keys(config('locales.supported')),
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $city = City::create([
            'country_id' => $data['country_id'],
            'status' => (bool) $data['status'],
        ]);

        $city->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.cities.index')->with('status', __('eys.city.created'));
    }

    public function edit(City $city): View
    {
        $city->load('translations');

        return view('eys.cities.edit', [
            'city' => $city,
            'locales' => array_keys(config('locales.supported')),
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $data = $this->validateData($request);

        $city->update([
            'country_id' => $data['country_id'],
            'status' => (bool) $data['status'],
        ]);

        $city->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.cities.index')->with('status', __('eys.city.updated'));
    }

    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('eys.cities.index')->with('status', __('eys.city.deleted'));
    }

    /** Bir ülkenin aktif şehirleri (cascade dropdown için JSON). */
    public function byCountry(Country $country): JsonResponse
    {
        $cities = $country->cities()
            ->active()
            ->with('translations')
            ->get()
            ->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->getTranslation()?->official_name ?? '—',
            ])
            ->sortBy('name')
            ->values();

        return response()->json($cities);
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');

        $rules = [
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'status' => ['required', 'in:0,1'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.official_name"] = [
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
            $official = $data[$locale]['official_name'] ?? null;

            if (blank($official)) {
                continue;
            }

            $payload[$locale] = ['official_name' => $official];
        }

        return $payload;
    }

    /**
     * Aktif dildeki ülke adlarından oluşan dropdown seçenekleri.
     *
     * @return array<string, string>
     */
    private function countryOptions(): array
    {
        return Country::with('translations')
            ->get()
            ->mapWithKeys(fn (Country $c) => [
                $c->id => $c->getTranslation()?->short_name
                    ?: $c->getTranslation()?->official_name
                    ?: '—',
            ])
            ->sort()
            ->toArray();
    }
}
