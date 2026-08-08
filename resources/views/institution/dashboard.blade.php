<x-institution.app-layout :title="__('institution.nav.dashboard')">
    <div class="ip-card">
        {{ __('Giriş yaptınız, hoş geldiniz :name.', ['name' => auth('institution')->user()->first_name ?? auth('institution')->user()->email]) }}
    </div>
</x-institution.app-layout>
