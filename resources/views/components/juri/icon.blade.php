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

    @case('assignments')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><path d="M5 4.5h14a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H5A1.5 1.5 0 013.5 18V6A1.5 1.5 0 015 4.5z"/><path d="M7.5 9h9M7.5 12.5h6M7.5 16h4"/></svg>
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

    @case('info')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}><circle cx="12" cy="12" r="9"/><path d="M12 10.5v6" stroke-linecap="round"/><path d="M12 7.5h.01" stroke-linecap="round"/></svg>
        @break

    @case('chevron-right')
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.19 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        @break
@endswitch
