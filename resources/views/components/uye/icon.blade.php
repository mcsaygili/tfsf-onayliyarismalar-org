@props(['name'])

{{--
    Üye panelindeki tüm ikonlar tek bir yerden yönetiliyor — sistem
    genelinde aynı çizgi kalınlığı (1.5) ve 24x24 viewBox kullanılıyor, yeni
    bir ikon eklerken burada bir @case tanımlamak yeterli.
--}}
@switch($name)
    @case('dashboard')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg>
        @break

    @case('account')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="8" r="3.25"/><path d="M4.5 20c1.4-3.8 4.3-5.75 7.5-5.75S18.1 16.2 19.5 20"/></svg>
        @break

    @case('logout')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M9 4.5H6a1.5 1.5 0 00-1.5 1.5v12A1.5 1.5 0 006 19.5h3"/><path d="M14.5 15.5L19 12l-4.5-3.5"/><path d="M19 12H9"/></svg>
        @break

    @case('chevron-down')
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.19l3.71-3.96a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
        @break

    @case('warning')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 3.5l9.5 16.5H2.5L12 3.5z" stroke-linejoin="round" stroke-linecap="round"/><path d="M12 9.5v5" stroke-linecap="round"/><path d="M12 17.75h.01" stroke-linecap="round"/></svg>
        @break

    @case('trash')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4.5 7h15M9.5 7V5.5a1 1 0 011-1h3a1 1 0 011 1V7M6.5 7l.7 12.2a1 1 0 001 .8h7.6a1 1 0 001-.8L17.5 7"/><path d="M10 11v6M14 11v6"/></svg>
        @break

    @case('lock')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="5.5" y="11" width="13" height="9" rx="1.5"/><path d="M8.5 11V8a3.5 3.5 0 017 0v3"/></svg>
        @break

    @case('chevron-right')
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.19 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        @break

    @case('camera')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4 8.5a1.5 1.5 0 011.5-1.5h1.6l1-1.6a1 1 0 01.85-.4h6.1a1 1 0 01.85.4l1 1.6h1.6A1.5 1.5 0 0120 8.5v9A1.5 1.5 0 0118.5 19h-13A1.5 1.5 0 014 17.5v-9z" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.5"/></svg>
        @break

    @case('upload')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 15.5V4.5M12 4.5l-4 4M12 4.5l4 4" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 15.5v3a1.5 1.5 0 001.5 1.5h12a1.5 1.5 0 001.5-1.5v-3" stroke-linecap="round"/></svg>
        @break

    @case('grid')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><rect x="14.5" y="14.5" width="6" height="6" rx="1"/></svg>
        @break

    @case('list')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M8.5 6.5h11.5M8.5 12h11.5M8.5 17.5h11.5" stroke-linecap="round"/><path d="M4 6.5h.01M4 12h.01M4 17.5h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @break

    @case('equipment')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="8.5" width="17" height="10.5" rx="1.5"/><path d="M8.5 8.5V6a1.5 1.5 0 011.5-1.5h4A1.5 1.5 0 0115.5 6v2.5"/><path d="M3.5 13h17" stroke-linecap="round"/></svg>
        @break

    @case('edit')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4 20h4L18.5 9.5a2.121 2.121 0 00-3-3L5 17v3z"/><path d="M13.5 6.5l4 4"/></svg>
        @break

    @case('back')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" {{ $attributes }}><path d="M15 5l-7 7 7 7"/></svg>
        @break

    @case('clock')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @break

    @case('calendar')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="4" y="5.5" width="16" height="14.5" rx="1.5"/><path d="M4 9.5h16" /><path d="M8 3.5v3.5M16 3.5v3.5" stroke-linecap="round"/></svg>
        @break
@endswitch
