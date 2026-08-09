<x-eys.app-layout :title="__('eys.users.manage_roles')">
    <div class="ip-page-actions" style="justify-content: space-between;">
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.users.list_title'), 'url' => route('eys.users.index')],
            ['label' => __('eys.users.manage_roles')],
        ]" />
        <a href="{{ route('eys.users.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="back" />
            {{ __('eys.common.back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('eys.users.roles.update', $user) }}" novalidate>
        @csrf
        @method('PATCH')

        <div class="ip-card">
            <div class="ip-section-title">{{ trim($user->first_name.' '.$user->last_name) ?: $user->email }}</div>
            <div class="ip-section-hint">{{ $user->email }}</div>

            @foreach ($modules as $module)
                @php $roles = $rolesByModule[$module->value] ?? collect(); @endphp
                <div style="border: 1px solid var(--ia-surface-border); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                    <div style="font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ia-muted-dim); margin-bottom: .75rem;">{{ $module->label() }}</div>
                    @forelse ($roles as $role)
                        <label style="display: flex; align-items: center; gap: .55rem; padding: .5rem .2rem; cursor: pointer; font-size: .88rem; color: var(--ia-cream);">
                            <input type="checkbox" name="roles[{{ $module->value }}][]" value="{{ $role->name }}" @checked(in_array($role->name, $current[$module->value] ?? [], true))>
                            {{ $role->label ?: $role->name }}
                        </label>
                    @empty
                        <p style="font-size: .85rem; color: var(--ia-muted-dim); font-style: italic; margin: 0;">{{ __('eys.role.empty') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1.5rem;">
            <x-eys.button>{{ __('eys.common.update') }}</x-eys.button>
        </div>
    </form>
</x-eys.app-layout>
