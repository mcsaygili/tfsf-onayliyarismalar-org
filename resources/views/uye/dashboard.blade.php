<x-uye.app-layout :title="__('uye.nav.dashboard')">
    <div class="ip-card">
        {{ __('uye.dashboard.welcome', ['name' => auth()->user()->first_name ?? auth()->user()->email]) }}
    </div>
</x-uye.app-layout>
