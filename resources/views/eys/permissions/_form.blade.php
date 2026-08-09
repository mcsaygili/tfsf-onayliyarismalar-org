@props(['permission', 'moduleLabels'])

<div class="ip-card">
    <div class="ip-section-title">{{ __('eys.permission.title') }}</div>

    <div class="ia-field">
        <x-eys.label for="module" :value="__('eys.permission.module')" />
        <select id="module" name="module" class="ia-input">
            @foreach ($moduleLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('module', $permission->module) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-eys.input-error :messages="$errors->get('module')" />
    </div>

    <div class="ia-field">
        <x-eys.label for="name" :value="__('eys.permission.name')" />
        <x-eys.input id="name" type="text" name="name" :value="old('name', $permission->name)" autocomplete="off" style="font-family: monospace;" />
        <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.permission.name_hint') }}</div>
        <x-eys.input-error :messages="$errors->get('name')" />
    </div>

    <div class="ip-grid-2" style="margin-bottom: 0;">
        <div class="ia-field">
            <x-eys.label for="group" :value="__('eys.permission.group')" />
            <x-eys.input id="group" type="text" name="group" :value="old('group', $permission->group)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('group')" />
        </div>
        <div class="ia-field" style="margin-bottom: 0;">
            <x-eys.label for="label" :value="__('eys.permission.label')" />
            <x-eys.input id="label" type="text" name="label" :value="old('label', $permission->label)" autocomplete="off" />
            <x-eys.input-error :messages="$errors->get('label')" />
        </div>
    </div>
</div>
