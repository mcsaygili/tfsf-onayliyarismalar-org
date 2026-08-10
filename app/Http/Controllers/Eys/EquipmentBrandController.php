<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EquipmentBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Marka (referans veri) yönetimi.
 *
 * Kod tabanındaki ilk çeviri gerektirmeyen referans veri ekranı — marka
 * adları (Canon, Nikon, vb.) dilden bağımsız, tek bir `name` sütunu.
 */
class EquipmentBrandController extends Controller
{
    public function index(Request $request): View
    {
        $equipmentBrands = EquipmentBrand::query()
            ->when($request->filled('name'),
                fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('eys.equipment-brands.index', [
            'equipmentBrands' => $equipmentBrands,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.equipment-brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        EquipmentBrand::create([
            'name' => $data['name'],
            'status' => (bool) $data['status'],
        ]);

        return redirect()->route('eys.equipment-brands.index')->with('status', __('eys.equipment_brand.created'));
    }

    public function edit(EquipmentBrand $equipmentBrand): View
    {
        return view('eys.equipment-brands.edit', [
            'equipmentBrand' => $equipmentBrand,
        ]);
    }

    public function update(Request $request, EquipmentBrand $equipmentBrand): RedirectResponse
    {
        $data = $this->validateData($request, $equipmentBrand);

        $equipmentBrand->update([
            'name' => $data['name'],
            'status' => (bool) $data['status'],
        ]);

        return redirect()->route('eys.equipment-brands.index')->with('status', __('eys.equipment_brand.updated'));
    }

    public function destroy(EquipmentBrand $equipmentBrand): RedirectResponse
    {
        $equipmentBrand->delete();

        return redirect()->route('eys.equipment-brands.index')->with('status', __('eys.equipment_brand.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?EquipmentBrand $equipmentBrand = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('equipment_brands', 'name')->whereNull('deleted_at')->ignore($equipmentBrand?->id),
            ],
            'status' => ['required', 'in:0,1'],
        ], [
            'name.unique' => __('eys.equipment_brand.name_unique'),
        ]);
    }
}
