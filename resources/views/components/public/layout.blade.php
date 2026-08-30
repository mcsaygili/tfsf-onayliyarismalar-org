@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title && $title !== __('public.site_name') ? $title.' — ' : '' }}{{ __('public.site_name') }}</title>
    <meta name="description" content="{{ $description ?: __('public.meta_description') }}">
    <meta property="og:title" content="{{ $title ?: __('public.site_name') }}">
    <meta property="og:description" content="{{ $description ?: __('public.meta_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pf-body">
    <a class="pf-skip-link" href="#main-content">{{ __('public.skip_to_content') }}</a>
    <header class="pf-header">
        <div class="pf-shell pf-header-inner">
            <a class="pf-brand" href="{{ route('public.home') }}">
                <span class="pf-brand-mark" aria-hidden="true"><x-public.icon name="competition-cup" /></span>
                <span><small>{{ __('public.federation') }}</small>{{ __('public.site_name') }}</span>
            </a>
            <nav class="pf-desktop-nav" aria-label="{{ __('public.navigation') }}">
                <a @class(['is-active' => request()->routeIs('public.competitions.*')]) href="{{ route('public.competitions.index') }}">{{ __('public.nav.competitions') }}</a>
                <a href="{{ route('result.index') }}">{{ __('public.nav.results') }}</a>
                <a href="{{ route('login') }}">{{ __('public.nav.member_login') }}</a>
                <span class="pf-language" aria-label="{{ __('public.language') }}">
                    @foreach(config('locales.supported') as $code => $label)
                        <a href="{{ route('public.language', $code) }}" @class(['is-active' => app()->getLocale() === $code]) lang="{{ $code }}">{{ strtoupper($code) }}</a>
                    @endforeach
                </span>
            </nav>
            <details class="pf-mobile-nav">
                <summary><x-public.icon name="menu" />{{ __('public.menu') }}</summary>
                <div>
                    <a href="{{ route('public.competitions.index') }}">{{ __('public.nav.competitions') }}</a>
                    <a href="{{ route('result.index') }}">{{ __('public.nav.results') }}</a>
                    <a href="{{ route('login') }}">{{ __('public.nav.member_login') }}</a>
                    @foreach(config('locales.supported') as $code => $label)
                        <a href="{{ route('public.language', $code) }}" lang="{{ $code }}">{{ $label }}</a>
                    @endforeach
                </div>
            </details>
        </div>
    </header>

    <main id="main-content" class="pf-main">{{ $slot }}</main>

    <footer class="pf-footer">
        <div class="pf-shell pf-footer-inner">
            <div><strong>{{ __('public.site_name') }}</strong><p>{{ __('public.footer_text') }}</p></div>
            <nav aria-label="{{ __('public.footer_navigation') }}">
                <a href="{{ route('public.competitions.index') }}">{{ __('public.nav.competitions') }}</a>
                <a href="{{ route('result.index') }}">{{ __('public.nav.results') }}</a>
                <a href="{{ route('login') }}">{{ __('public.nav.member_login') }}</a>
            </nav>
        </div>
    </footer>
</body>
</html>
