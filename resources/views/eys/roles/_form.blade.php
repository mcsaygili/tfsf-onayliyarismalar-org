@props([
    'role',
    'moduleLabels',
    'permissionsByModule',
    'selectedPermissions',
    'moduleEditable' => true,
    'fixedModuleName' => null,
])

@php
    $initialModule = old('module', $moduleEditable ? array_key_first($moduleLabels) : $fixedModuleName);
@endphp

<div x-data="{ mod: @js($initialModule) }">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div class="ip-section-title">{{ __('eys.role.title') }}</div>

        <div class="ia-field">
            <x-eys.label for="module" :value="__('eys.role.module')" />
            @if ($moduleEditable)
                <select id="module" name="module" x-model="mod" class="ia-input">
                    @foreach ($moduleLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <div class="ia-input" style="cursor: default;">{{ $moduleLabels[$fixedModuleName] ?? '' }}</div>
            @endif
            <x-eys.input-error :messages="$errors->get('module')" />
        </div>

        <div class="ip-grid-2">
            <div class="ia-field">
                <x-eys.label for="name" :value="__('eys.role.name')" />
                <x-eys.input id="name" type="text" name="name" :value="old('name', $role->name)" autocomplete="off" style="font-family: monospace;" />
                <div style="font-size: .78rem; color: var(--ia-muted-dim); margin-top: .35rem;">{{ __('eys.role.name_hint') }}</div>
                <x-eys.input-error :messages="$errors->get('name')" />
            </div>
            <div class="ia-field" style="margin-bottom: 0;">
                <x-eys.label for="label" :value="__('eys.role.label')" />
                <x-eys.input id="label" type="text" name="label" :value="old('label', $role->label)" autocomplete="off" />
                <x-eys.input-error :messages="$errors->get('label')" />
            </div>
        </div>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('eys.role.permissions') }}</div>
        <div class="ip-section-hint">{{ __('eys.role.permissions_intro') }}</div>

        @foreach ($permissionsByModule as $moduleName => $groups)
            <div x-show="mod === @js($moduleName)" x-cloak>
                @forelse ($groups as $groupLabel => $perms)
                    <div style="border: 1px solid var(--ia-surface-border); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ia-muted-dim); margin-bottom: .75rem;">{{ $groupLabel }}</div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .6rem;">
                            @foreach ($perms as $perm)
                                @php
                                    $actionLabel = str_contains((string) $perm->label, '·')
                                        ? trim(\Illuminate\Support\Str::afterLast($perm->label, '·'))
                                        : ($perm->label ?: \Illuminate\Support\Str::afterLast($perm->name, '.'));
                                @endphp
                                <label style="display: flex; align-items: center; gap: .55rem; padding: .5rem .7rem; border-radius: 7px; border: 1px solid var(--ia-surface-border); cursor: pointer; font-size: .85rem; color: var(--ia-cream);" title="{{ $perm->name }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" @checked(in_array($perm->name, $selectedPermissions, true))>
                                    {{ $actionLabel }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p style="font-size: .85rem; color: var(--ia-muted-dim); font-style: italic;">{{ __('eys.role.no_permissions') }}</p>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
