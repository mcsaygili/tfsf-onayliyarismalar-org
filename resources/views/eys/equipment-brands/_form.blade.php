@props(['equipmentBrand'])

<div class="ip-card">
    <div class="ip-section-title">{{ __('eys.equipment_brand.title') }}</div>

    <div class="ip-grid-2">
        <div class="ia-field" style="margin-bottom: 0;">
            <x-eys.label for="name" :value="__('eys.equipment_brand.name')" />
            <x-eys.input id="name" type="text" name="name" :value="old('name', $equipmentBrand->name)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('name')" />
        </div>
        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $equipmentBrand->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.equipment_brand.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.equipment_brand.status_active')) : @js(__('eys.equipment_brand.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>
</div>
