<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use App\Models\EquipmentTypeTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Ekipman Türü (referans veri) yönetimi.
 *
 * Çok dilli içerik: equipment_types (sıra, durum) + equipment_type_translations
 * (ad). Form her dil için ayrı sekme sunar (bkz. Öğrenim Durumu ile aynı desen).
 */
class EquipmentTypeController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $equipmentTypes = EquipmentType::with('translations')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = $request->string('name');
                $q->whereHas('translations', fn ($t) => $t->where('name', 'like', "%{$name}%"));
            })
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('sort_order')
            ->orderBy(
                EquipmentTypeTranslation::select('name')
                    ->whereColumn('equipment_type_id', 'equipment_types.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.equipment-types.index', [
            'equipmentTypes' => $equipmentTypes,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.equipment-types.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $equipmentType = EquipmentType::create([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $equipmentType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.equipment-types.index')->with('status', __('eys.equipment_type.created'));
    }

    public function edit(EquipmentType $equipmentType): View
    {
        $equipmentType->load('translations');

        return view('eys.equipment-types.edit', [
            'equipmentType' => $equipmentType,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, EquipmentType $equipmentType): RedirectResponse
    {
        $data = $this->validateData($request);

        $equipmentType->update([
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $equipmentType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.equipment-types.index')->with('status', __('eys.equipment_type.updated'));
    }

    public function destroy(EquipmentType $equipmentType): RedirectResponse
    {
        $equipmentType->delete();

        return redirect()->route('eys.equipment-types.index')->with('status', __('eys.equipment_type.deleted'));
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
