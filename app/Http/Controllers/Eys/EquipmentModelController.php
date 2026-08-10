<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Ekipman Modeli (referans veri) yönetimi.
 *
 * Bir Marka'ya ve bir Ekipman Türü'ne bağlı, çeviri gerektirmeyen hiyerarşik
 * referans veri. Marka/Tür seçimleri City'nin ülke seçimi deseninde,
 * sunucu tarafında tam doldurulmuş statik select'ler — canlı JSON-cascade
 * SADECE Üye tarafındaki "Ekipmanlarım" formunda kullanılıyor, burada değil.
 */
class EquipmentModelController extends Controller
{
    public function index(Request $request): View
    {
        $equipmentModels = EquipmentModel::with(['brand', 'type.translations'])
            ->when($request->filled('name'),
                fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('equipment_brand_id'),
                fn ($q) => $q->where('equipment_brand_id', $request->input('equipment_brand_id')))
            ->when($request->filled('equipment_type_id'),
                fn ($q) => $q->where('equipment_type_id', $request->input('equipment_type_id')))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('eys.equipment-models.index', [
            'equipmentModels' => $equipmentModels,
            'brandOptions' => $this->brandOptions(),
            'typeOptions' => $this->typeOptions(),
            'filter' => [
                'name' => $request->input('name', ''),
                'equipment_brand_id' => $request->input('equipment_brand_id', ''),
                'equipment_type_id' => $request->input('equipment_type_id', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.equipment-models.create', [
            'brandOptions' => $this->brandOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        EquipmentModel::create([
            'equipment_brand_id' => $data['equipment_brand_id'],
            'equipment_type_id' => $data['equipment_type_id'],
            'name' => $data['name'],
            'status' => (bool) $data['status'],
        ]);

        return redirect()->route('eys.equipment-models.index')->with('status', __('eys.equipment_model.created'));
    }

    public function edit(EquipmentModel $equipmentModel): View
    {
        return view('eys.equipment-models.edit', [
            'equipmentModel' => $equipmentModel,
            'brandOptions' => $this->brandOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(Request $request, EquipmentModel $equipmentModel): RedirectResponse
    {
        $data = $this->validateData($request, $equipmentModel);

        $equipmentModel->update([
            'equipment_brand_id' => $data['equipment_brand_id'],
            'equipment_type_id' => $data['equipment_type_id'],
            'name' => $data['name'],
            'status' => (bool) $data['status'],
        ]);

        return redirect()->route('eys.equipment-models.index')->with('status', __('eys.equipment_model.updated'));
    }

    public function destroy(EquipmentModel $equipmentModel): RedirectResponse
    {
        $equipmentModel->delete();

        return redirect()->route('eys.equipment-models.index')->with('status', __('eys.equipment_model.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?EquipmentModel $equipmentModel = null): array
    {
        return $request->validate([
            'equipment_brand_id' => ['required', 'uuid', 'exists:equipment_brands,id'],
            'equipment_type_id' => ['required', 'uuid', 'exists:equipment_types,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('equipment_models', 'name')
                    ->where(fn ($q) => $q->where('equipment_brand_id', $request->input('equipment_brand_id')))
                    ->whereNull('deleted_at')
                    ->ignore($equipmentModel?->id),
            ],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.unique' => __('eys.equipment_model.name_unique'),
        ]);
    }

    /** @return array<string, string> */
    private function brandOptions(): array
    {
        return EquipmentBrand::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    /** @return array<string, string> */
    private function typeOptions(): array
    {
        return EquipmentType::with('translations')
            ->get()
            ->mapWithKeys(fn (EquipmentType $t) => [$t->id => $t->getTranslation()?->name ?? '—'])
            ->sort()
            ->toArray();
    }
}
