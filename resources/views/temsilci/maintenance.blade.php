{{-- $message dışarıdan geliyor (CheckMaintenanceMode middleware) --}}
<x-temsilci.guest-layout
    :heading="__('temsilci.maintenance.heading')"
    :subheading="__('temsilci.maintenance.subheading')"
>
    <div class="ia-card-label ia-rise ia-d3">{{ __('temsilci.maintenance.card_label') }}</div>
    <h2 class="ia-card-title ia-rise ia-d3">{{ __('temsilci.maintenance.card_title') }}</h2>

    <div class="ia-rise ia-d3" style="display: flex; gap: .75rem; align-items: flex-start; margin: 1rem 0 0; padding: 1rem 1.1rem; border-radius: 10px; background: rgba(224,178,122,.1); border: 1px solid rgba(224,178,122,.3); color: #e6c896;">
        <x-temsilci.icon name="warning" style="width: 22px; height: 22px; flex-shrink: 0; margin-top: .1rem;" />
        <div style="font-size: .9rem; line-height: 1.55;">
            {{ $message ?: __('temsilci.maintenance.default_message') }}
        </div>
    </div>
</x-temsilci.guest-layout>
