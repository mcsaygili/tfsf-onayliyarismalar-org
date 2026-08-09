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
@endswitch
