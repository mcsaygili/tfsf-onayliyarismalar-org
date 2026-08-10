<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uye\EquipmentStoreRequest;
use App\Http\Requests\Uye\EquipmentUpdateRequest;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\UserEquipment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Üye (fotoğrafçı) kişisel ekipman envanteri ("Ekipmanlarım") —
 * PortfolioController'daki authorizeOwner() sahiplik deseninin kopyası.
 */
class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $equipment = $request->user()->equipment()
            ->with('equipmentModel.brand', 'equipmentModel.type.translations')
            ->latest()
            ->get();

        return view('uye.equipment.index', [
            'equipment' => $equipment,
            'equipmentBrandOptions' => EquipmentBrand::active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('uye.equipment.create', [
            'equipmentBrandOptions' => EquipmentBrand::active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(EquipmentStoreRequest $request): RedirectResponse
    {
        $request->user()->equipment()->create([
            'equipment_model_id' => $request->input('equipment_model_id'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('equipment.index')->with('status', __('uye.equipment.created'));
    }

    public function edit(Request $request, UserEquipment $userEquipment): View
    {
        $this->authorizeOwner($userEquipment, $request);

        $userEquipment->load('equipmentModel.brand', 'equipmentModel.type.translations');

        return view('uye.equipment.edit', [
            'userEquipment' => $userEquipment,
        ]);
    }

    public function update(EquipmentUpdateRequest $request, UserEquipment $userEquipment): RedirectResponse
    {
        $this->authorizeOwner($userEquipment, $request);

        $userEquipment->update([
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('equipment.index')->with('status', __('uye.equipment.updated'));
    }

    public function destroy(Request $request, UserEquipment $userEquipment): RedirectResponse
    {
        $this->authorizeOwner($userEquipment, $request);

        $userEquipment->delete();

        return redirect()->route('equipment.index')->with('status', __('uye.equipment.deleted'));
    }

    /** Bir markanın aktif modelleri (cascade dropdown için JSON). */
    public function modelsByBrand(EquipmentBrand $equipmentBrand): JsonResponse
    {
        $models = $equipmentBrand->models()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (EquipmentModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
            ])
            ->values();

        return response()->json($models);
    }

    private function authorizeOwner(UserEquipment $userEquipment, Request $request): void
    {
        if ($userEquipment->user_id !== $request->user()->id) {
            throw new AuthorizationException;
        }
    }
}
