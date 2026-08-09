@props(['institution', 'institutionTypes'])

<div class="ip-card" style="margin-bottom: 1.5rem;">
    <div class="ip-section-title">{{ __('eys.institution.title') }}</div>

    <div class="ia-field">
        <x-eys.label for="name" :value="__('eys.institution.name')" />
        <x-eys.input id="name" type="text" name="name" :value="old('name', $institution->name)" autocomplete="off" />
        <x-eys.input-error :messages="$errors->get('name')" />
    </div>

    <div class="ip-grid-2">
        <div class="ia-field">
            <x-eys.label for="email" :value="__('eys.institution.email')" />
            <x-eys.input id="email" type="email" name="email" :value="old('email', $institution->email)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('email')" />
        </div>
        <div class="ia-field">
            <x-eys.label for="phone" :value="__('eys.institution.phone')" />
            <x-eys.input id="phone" type="text" name="phone" :value="old('phone', $institution->phone)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('phone')" />
        </div>
    </div>

    <div class="ip-grid-2">
        <div class="ia-field">
            <x-eys.label for="website" :value="__('eys.institution.website')" />
            <x-eys.input id="website" type="text" name="website" :value="old('website', $institution->website)" autocomplete="off" placeholder="https://" />
            <x-eys.input-error :messages="$errors->get('website')" />
        </div>
        <div class="ia-field">
            <x-eys.label for="institution_type_id" :value="__('eys.institution.type')" />
            <select id="institution_type_id" name="institution_type_id" class="ia-input">
                <option value="">{{ __('eys.institution.type_none') }}</option>
                @foreach ($institutionTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('institution_type_id', $institution->institution_type_id) === $type->id)>{{ $type->getTranslation()?->name }}</option>
                @endforeach
            </select>
            <x-eys.input-error :messages="$errors->get('institution_type_id')" />
        </div>
    </div>

    <div class="ia-field">
        <x-eys.label for="address" :value="__('eys.institution.address')" />
        <textarea id="address" name="address" class="ia-input" rows="3">{{ old('address', $institution->address) }}</textarea>
        <x-eys.input-error :messages="$errors->get('address')" />
    </div>

    <div class="ia-field" style="margin-bottom: 0;" x-data="{ active: {{ old('status', (int) $institution->status ?: 1) ? 'true' : 'false' }} }">
        <x-eys.label :value="__('eys.institution.status')" />
        <label class="ip-switch">
            <input type="hidden" name="status" :value="active ? 1 : 0">
            <input type="checkbox" class="ip-switch-checkbox" x-model="active">
            <span class="ip-switch-track"><span class="ip-switch-thumb"></span></span>
            <span class="ip-switch-label" x-text="active ? @js(__('eys.institution.status_active')) : @js(__('eys.institution.status_inactive'))"></span>
        </label>
        <x-eys.input-error :messages="$errors->get('status')" />
    </div>
</div>
