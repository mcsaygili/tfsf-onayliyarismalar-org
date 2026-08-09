@props(['name'])

{{--
    Kurum panelindeki tüm ikonlar tek bir yerden yönetiliyor — sistem
    genelinde aynı çizgi kalınlığı (1.5) ve 24x24 viewBox kullanılıyor, yeni
    bir ikon eklerken burada bir @case tanımlamak yeterli.
--}}
@switch($name)
    @case('dashboard')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg>
        @break

    @case('institution')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4.5 20.5V6.5a1 1 0 011-1h13a1 1 0 011 1v14"/><path d="M9 20.5v-5.5h6v5.5"/><path d="M8.5 9h.01M12 9h.01M15.5 9h.01M8.5 12.25h.01M12 12.25h.01M15.5 12.25h.01"/></svg>
        @break

    @case('staff')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="9" cy="8" r="2.75"/><path d="M3.5 20c1.1-3.2 3.2-4.85 5.5-4.85S13.9 16.8 15 20"/><circle cx="17" cy="8.5" r="2.1"/><path d="M15.3 15.6c1.9.3 3.3 1.7 4.2 4.4"/></svg>
        @break

    @case('account')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="8" r="3.25"/><path d="M4.5 20c1.4-3.8 4.3-5.75 7.5-5.75S18.1 16.2 19.5 20"/></svg>
        @break

    @case('logout')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M9 4.5H6a1.5 1.5 0 00-1.5 1.5v12A1.5 1.5 0 006 19.5h3"/><path d="M14.5 15.5L19 12l-4.5-3.5"/><path d="M19 12H9"/></svg>
        @break

    @case('back')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" {{ $attributes }}><path d="M15 5l-7 7 7 7"/></svg>
        @break

    @case('edit')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4 20h4L18.5 9.5a2.121 2.121 0 00-3-3L5 17v3z"/><path d="M13.5 6.5l4 4"/></svg>
        @break

    @case('chevron-down')
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.19l3.71-3.96a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
        @break

    @case('warning')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 3.5l9.5 16.5H2.5L12 3.5z" stroke-linejoin="round" stroke-linecap="round"/><path d="M12 9.5v5" stroke-linecap="round"/><path d="M12 17.75h.01" stroke-linecap="round"/></svg>
        @break

    @case('role')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 3.5l7 3v5.2c0 4.2-2.9 7.8-7 9.3-4.1-1.5-7-5.1-7-9.3V6.5l7-3z"/><path d="M9 12l2 2 4-4.5"/></svg>
        @break

    @case('permission')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="8" cy="8" r="4"/><path d="M11 11l9.5 9.5"/><path d="M16.5 15l2.5-2.5"/><path d="M18.5 17l2-2"/></svg>
        @break

    @case('country')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.2 2.3 3.4 5.3 3.4 8.5s-1.2 6.2-3.4 8.5c-2.2-2.3-3.4-5.3-3.4-8.5S9.8 5.8 12 3.5z"/></svg>
        @break

    @case('city')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4 20.5V9l5-3.5V20.5"/><path d="M9 20.5V5.5l6 3v12"/><path d="M15 20.5V9l5 2.5v9"/><path d="M6.5 11h1M6.5 14h1M6.5 17h1M11.5 9.5h1M11.5 12.5h1M11.5 15.5h1M17 13h1M17 16h1"/></svg>
        @break

    @case('file-manager')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M3.5 7.5a1 1 0 011-1h4.5l2 2h8.5a1 1 0 011 1v9a1 1 0 01-1 1h-15a1 1 0 01-1-1v-11z"/></svg>
        @break

    @case('mail')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="M4.5 6.5l7.5 6.5 7.5-6.5"/></svg>
        @break

    @case('chevron-right')
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.19 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        @break

    @case('temsilci')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="8" r="3.25"/><path d="M4.5 20c1.4-3.8 4.3-5.75 7.5-5.75S18.1 16.2 19.5 20"/><path d="M4.5 4.5l2 2M19.5 4.5l-2 2"/></svg>
        @break

    @case('juri')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 3.5v3M9 6.5h6"/><circle cx="12" cy="12" r="7"/><path d="M12 8.5v4l2.5 2"/></svg>
        @break

    @case('trash')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4.5 7h15M9.5 7V5.5a1 1 0 011-1h3a1 1 0 011 1V7M6.5 7l.7 12.2a1 1 0 001 .8h7.6a1 1 0 001-.8L17.5 7"/><path d="M10 11v6M14 11v6"/></svg>
        @break

    @case('uye')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="9" cy="8" r="2.75"/><path d="M2.5 20c1.1-3.2 3.2-4.85 5.5-4.85S13.9 16.8 15 20"/><path d="M16.5 8.5h5M19 6v5"/></svg>
        @break

    @case('upload')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 15.5V4.5M7.5 9L12 4.5 16.5 9"/><path d="M4.5 15.5v3a2 2 0 002 2h11a2 2 0 002-2v-3"/></svg>
        @break

    @case('folder')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M3.5 7.5a1 1 0 011-1h4.5l2 2h8.5a1 1 0 011 1v9a1 1 0 01-1 1h-15a1 1 0 01-1-1v-11z"/></svg>
        @break

    @case('arrow-up')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 19V5M6 11l6-6 6 6"/></svg>
        @break

    @case('copy')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="8.5" y="8.5" width="12" height="12" rx="1.5"/><path d="M15.5 8.5V5a1.5 1.5 0 00-1.5-1.5H5A1.5 1.5 0 003.5 5v9A1.5 1.5 0 005 15.5h3.5"/></svg>
        @break

    @case('grid')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg>
        @break

    @case('layers')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><rect x="4" y="4.5" width="16" height="3.5" rx="1"/><rect x="4" y="10.25" width="16" height="3.5" rx="1"/><rect x="4" y="16" width="16" height="3.5" rx="1"/></svg>
        @break

    @case('close')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M5 5l14 14M19 5L5 19"/></svg>
        @break

    @case('filter')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M4 5.5h16M7.5 12h9M10.5 18.5h3" stroke-linecap="round"/></svg>
        @break

    @case('plus')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M12 4.5v15M4.5 12h15" stroke-linecap="round"/></svg>
        @break

    @case('search')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="10.5" cy="10.5" r="6.5"/><path d="M19.5 19.5l-4.3-4.3" stroke-linecap="round"/></svg>
        @break

    @case('education')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M2.5 8.5L12 4l9.5 4.5L12 13 2.5 8.5z" stroke-linejoin="round"/><path d="M6.5 10.5v4.5c0 1.4 2.5 2.5 5.5 2.5s5.5-1.1 5.5-2.5v-4.5" stroke-linejoin="round"/><path d="M21 8.5v6" stroke-linecap="round"/></svg>
        @break

    @case('document')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M6.5 3.5h8l4 4v13a1 1 0 01-1 1h-11a1 1 0 01-1-1v-16a1 1 0 011-1z" stroke-linejoin="round"/><path d="M14.5 3.5v4h4" stroke-linejoin="round"/><path d="M8.5 12.5h7M8.5 15.5h7M8.5 18h4.5" stroke-linecap="round"/></svg>
        @break

    @case('list-check')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M3.5 6l1.5 1.5L8 4.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 13l1.5 1.5L8 11.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.5 20l1.5 1.5L8 18.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.5 6h9M11.5 13h9M11.5 20h9" stroke-linecap="round"/></svg>
        @break
@endswitch
