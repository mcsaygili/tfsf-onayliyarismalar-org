{{-- $userEquipment dışarıdan geliyor (EquipmentController::edit()) --}}
<x-uye.app-layout :title="__('uye.equipment.edit_title')">
    <div class="ip-page-actions">
        <a href="{{ route('equipment.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-uye.icon name="back" />
            {{ __('uye.equipment.back_to_list') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ $userEquipment->equipmentModel->brand?->name }} {{ $userEquipment->equipmentModel->name }}</div>
        <div class="ip-section-hint">{{ $userEquipment->equipmentModel->type?->getTranslation()?->name ?? '—' }}</div>

        <form method="POST" action="{{ route('equipment.update', $userEquipment) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ia-field" style="margin-bottom: 0;">
                <x-uye.label for="notes" :value="__('uye.equipment.field_notes')" />
                <textarea id="notes" name="notes" class="ia-input" rows="3">{{ $userEquipment->notes }}</textarea>
            </div>

            <div class="ip-modal-actions" style="justify-content: space-between; margin-top: 1.5rem;">
                <button type="button" class="ia-btn ia-btn-danger" onclick="uyeConfirm(@js(__('uye.equipment.delete_confirm_text')), document.getElementById('delete-equipment-{{ $userEquipment->id }}'))">
                    <x-uye.icon name="trash" />
                    {{ __('uye.equipment.delete_equipment') }}
                </button>

                <x-uye.button>{{ __('uye.equipment.save') }}</x-uye.button>
            </div>
        </form>

        <form id="delete-equipment-{{ $userEquipment->id }}" method="POST" action="{{ route('equipment.destroy', $userEquipment) }}" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-uye.app-layout>
