{{-- $equipmentBrandOptions dışarıdan geliyor (EquipmentController::create()) --}}
<x-uye.app-layout :title="__('uye.equipment.create_title')">
    <div class="ip-page-actions">
        <a href="{{ route('equipment.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-uye.icon name="back" />
            {{ __('uye.equipment.back_to_list') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('uye.equipment.create_title') }}</div>
        <div class="ip-section-hint">{{ __('uye.equipment.create_hint') }}</div>

        <form method="POST" action="{{ route('equipment.store') }}" novalidate autocomplete="off"
            x-data="{
                brandId: @js(old('_brand_id', '')),
                models: [],
                selectedModelId: @js(old('equipment_model_id', '')),
                loadModels() {
                    this.models = [];
                    this.selectedModelId = '';
                    if (! this.brandId) return;
                    fetch(`/ekipmanlarim/markalar/${this.brandId}/modeller`)
                        .then(r => r.json())
                        .then(data => this.models = data);
                }
            }"
        >
            @csrf

            <div class="ia-field">
                <x-uye.label for="_brand_id" :value="__('uye.equipment.field_brand')" />
                <select id="_brand_id" name="_brand_id" class="ia-input" x-model="brandId" @change="loadModels()">
                    <option value="">{{ __('uye.equipment.select_brand') }}</option>
                    @foreach ($equipmentBrandOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ia-field">
                <x-uye.label for="equipment_model_id" :value="__('uye.equipment.field_model')" />
                <select id="equipment_model_id" name="equipment_model_id" class="ia-input" x-model="selectedModelId" :disabled="! models.length">
                    <option value="">{{ __('uye.equipment.select_model') }}</option>
                    <template x-for="model in models" :key="model.id">
                        <option :value="model.id" x-text="model.name" :selected="model.id === selectedModelId"></option>
                    </template>
                </select>
                <x-uye.input-error :messages="$errors->get('equipment_model_id')" />
            </div>

            <div class="ia-field" style="margin-bottom: 0;">
                <x-uye.label for="notes" :value="__('uye.equipment.field_notes')" />
                <textarea id="notes" name="notes" class="ia-input" rows="2">{{ old('notes') }}</textarea>
                <x-uye.input-error :messages="$errors->get('notes')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-uye.button>{{ __('uye.equipment.save') }}</x-uye.button>
            </div>
        </form>
    </div>
</x-uye.app-layout>
