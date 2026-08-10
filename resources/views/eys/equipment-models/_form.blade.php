@props(['equipmentModel', 'brandOptions', 'typeOptions'])

<div class="ip-card">
    <div class="ip-section-title">{{ __('eys.equipment_model.title') }}</div>

    <div class="ip-grid-2">
        <div class="ia-field">
            <x-eys.label for="equipment_brand_id" :value="__('eys.equipment_model.brand')" />
            <select id="equipment_brand_id" name="equipment_brand_id" class="ia-input">
                <option value="" @selected(! old('equipment_brand_id', $equipmentModel->equipment_brand_id))>—</option>
                @foreach ($brandOptions as $id => $label)
                    <option value="{{ $id }}" @selected(old('equipment_brand_id', $equipmentModel->equipment_brand_id) === $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-eys.input-error :messages="$errors->get('equipment_brand_id')" />
        </div>
        <div class="ia-field">
            <x-eys.label for="equipment_type_id" :value="__('eys.equipment_model.type')" />
            <select id="equipment_type_id" name="equipment_type_id" class="ia-input">
                <option value="" @selected(! old('equipment_type_id', $equipmentModel->equipment_type_id))>—</option>
                @foreach ($typeOptions as $id => $label)
                    <option value="{{ $id }}" @selected(old('equipment_type_id', $equipmentModel->equipment_type_id) === $id)>{{ $label }}</option>
                @endforeach
            </select>
            <x-eys.input-error :messages="$errors->get('equipment_type_id')" />
        </div>
    </div>

    <div class="ip-grid-2">
        <div class="ia-field" style="margin-bottom: 0;">
            <x-eys.label for="name" :value="__('eys.equipment_model.name')" />
            <x-eys.input id="name" type="text" name="name" :value="old('name', $equipmentModel->name)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('name')" />
        </div>
        <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $equipmentModel->status ?: 1) ? 'true' : 'false' }} }">
            <x-eys.label :value="__('eys.equipment_model.status')" />
            <label class="ip-switch">
                <input type="hidden" name="status" :value="active ? 1 : 0">
                <input type="checkbox" class="ip-switch-checkbox" x-model="active">
                <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
                <span class="ip-switch-label" x-text="active ? @js(__('eys.equipment_model.status_active')) : @js(__('eys.equipment_model.status_inactive'))"></span>
            </label>
            <x-eys.input-error :messages="$errors->get('status')" />
        </div>
    </div>
</div>
