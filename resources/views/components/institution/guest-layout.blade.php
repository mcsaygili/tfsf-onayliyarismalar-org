@props([
    'eyebrow' => null,
    'heading' => null,
    'subheading' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="ia-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $heading }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600|ibm-plex-mono:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .ia-shell {
            --ia-bg: #120f0c;
            --ia-bg-soft: #171310;
            --ia-surface: #1d1710;
            --ia-surface-border: #3a2f21;
            --ia-copper: #d98936;
            --ia-copper-bright: #f0ab52;
            --ia-cream: #f4ecdd;
            --ia-muted: #9c8f7c;
            --ia-muted-dim: #6b6152;
            --ia-red: #c1432e;
            --ia-focus: rgba(217, 137, 54, .28);
            min-height: 100vh;
            background:
                radial-gradient(ellipse 900px 700px at 82% -10%, rgba(217,137,54,.10), transparent 60%),
                radial-gradient(ellipse 1200px 900px at 50% 120%, rgba(0,0,0,.5), transparent 60%),
                var(--ia-bg);
            color: var(--ia-cream);
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            position: relative;
            overflow-x: hidden;
        }

        .ia-noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .05;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .ia-lang {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .7rem;
            letter-spacing: .1em;
        }
        .ia-lang a {
            color: var(--ia-muted-dim);
            text-decoration: none;
            transition: color .15s ease;
        }
        .ia-lang a:hover { color: var(--ia-muted); }
        .ia-lang a.is-active {
            color: var(--ia-copper-bright);
        }
        .ia-lang span {
            color: var(--ia-surface-border);
        }

        .ia-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            min-height: 100vh;
        }
        @media (min-width: 1024px) {
            .ia-grid { grid-template-columns: 1.15fr 1fr; }
        }

        .ia-art {
            position: relative;
            overflow: hidden;
            padding: 3rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 1px solid var(--ia-surface-border);
            background: linear-gradient(180deg, var(--ia-bg-soft), var(--ia-bg));
        }
        @media (min-width: 1024px) {
            .ia-art {
                padding: 4rem 5rem;
                border-bottom: none;
                border-right: 1px solid var(--ia-surface-border);
            }
        }

        .ia-aperture-wrap {
            position: absolute;
            top: 50%;
            right: 4%;
            transform: translateY(-50%);
            width: 560px;
            height: 560px;
            pointer-events: none;
            opacity: .55;
        }
        @media (max-width: 1023px) {
            .ia-aperture-wrap { right: -30%; width: 460px; height: 460px; opacity: .4; }
        }

        .ia-sprockets {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 1.1rem;
            width: 10px;
            display: none;
            flex-direction: column;
            justify-content: space-evenly;
            align-items: center;
        }
        @media (min-width: 1024px) {
            .ia-sprockets { display: flex; }
        }
        .ia-sprockets span {
            width: 8px;
            height: 12px;
            border-radius: 2px;
            background: var(--ia-surface-border);
        }

        .ia-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: .72rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--ia-copper-bright);
            display: inline-flex;
            align-items: center;
            gap: .6em;
        }
        .ia-eyebrow::before {
            content: '';
            width: 22px;
            height: 1px;
            background: var(--ia-copper);
            display: inline-block;
        }

        .ia-headline {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 500;
            font-size: clamp(2.4rem, 5vw, 3.6rem);
            line-height: 1.05;
            letter-spacing: -.01em;
            color: var(--ia-cream);
            margin-top: 1.1rem;
            max-width: 14ch;
        }
        .ia-headline em {
            font-style: italic;
            color: var(--ia-copper-bright);
        }

        .ia-sub {
            margin-top: 1.15rem;
            max-width: 34ch;
            color: var(--ia-muted);
            font-size: .95rem;
            line-height: 1.65;
        }

        .ia-form-col {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }
        @media (min-width: 1024px) {
            .ia-form-col { padding: 3rem; }
        }

        .ia-card {
            width: 100%;
            max-width: 400px;
        }

        .ia-card-label {
            font-size: .72rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            margin-bottom: .5rem;
        }

        .ia-card-title {
            font-family: 'Fraunces', serif;
            font-size: 1.6rem;
            color: var(--ia-cream);
            margin-bottom: 2rem;
        }

        .ia-field { margin-bottom: 1.35rem; }

        .ia-label {
            display: block;
            font-size: .7rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--ia-muted);
            margin-bottom: .55rem;
        }

        .ia-input {
            width: 100%;
            background: var(--ia-surface);
            border: 1px solid var(--ia-surface-border);
            border-radius: 3px;
            padding: .72rem .85rem;
            color: var(--ia-cream);
            font-family: 'IBM Plex Mono', monospace;
            font-size: .92rem;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .ia-input::placeholder { color: var(--ia-muted-dim); }
        .ia-input:focus {
            outline: none;
            border-color: var(--ia-copper);
            background: #221b12;
            box-shadow: 0 0 0 3px var(--ia-focus);
        }

        .ia-error {
            margin-top: .5rem;
            font-size: .78rem;
            color: #e08579;
        }

        .ia-status {
            margin-bottom: 1.5rem;
            padding: .75rem .9rem;
            border: 1px solid #3c5c3f;
            background: rgba(88, 140, 92, .12);
            color: #a9d2ac;
            font-size: .82rem;
            border-radius: 3px;
        }

        .ia-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0 1.75rem;
            font-size: .82rem;
        }

        .ia-check {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            color: var(--ia-muted);
            cursor: pointer;
            user-select: none;
        }
        .ia-check input {
            appearance: none;
            width: 15px;
            height: 15px;
            border: 1px solid var(--ia-surface-border);
            background: var(--ia-surface);
            border-radius: 2px;
            display: inline-grid;
            place-content: center;
            cursor: pointer;
        }
        .ia-check input::before {
            content: '';
            width: 7px;
            height: 7px;
            transform: scale(0);
            border-radius: 1px;
            background: var(--ia-copper-bright);
            transition: transform .1s ease-in-out;
        }
        .ia-check input:checked::before { transform: scale(1); }

        .ia-link {
            color: var(--ia-copper-bright);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color .15s ease;
        }
        .ia-link:hover { border-color: var(--ia-copper-bright); }

        .ia-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: linear-gradient(180deg, var(--ia-copper-bright), var(--ia-copper));
            color: #1a1208;
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 600;
            font-size: .82rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: .85rem 1rem;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            transition: filter .15s ease, transform .1s ease;
        }
        .ia-btn:hover { filter: brightness(1.08); }
        .ia-btn:active { transform: translateY(1px); }

        .ia-foot {
            margin-top: 2.25rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--ia-surface-border);
            font-size: .72rem;
            letter-spacing: .06em;
            color: var(--ia-muted-dim);
            display: flex;
            justify-content: space-between;
        }

        @keyframes ia-rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .ia-rise { animation: ia-rise .6s cubic-bezier(.2,.7,.2,1) both; }
        .ia-d1 { animation-delay: .05s; }
        .ia-d2 { animation-delay: .15s; }
        .ia-d3 { animation-delay: .25s; }
        .ia-d4 { animation-delay: .35s; }
    </style>
</head>
<body class="ia-shell">
    <div class="ia-noise"></div>

    <nav class="ia-lang" aria-label="{{ __('institution.eyebrow') }}">
        @foreach (config('locales.supported') as $code => $label)
            <a href="{{ route('institution.language', $code) }}" class="{{ app()->getLocale() === $code ? 'is-active' : '' }}">{{ strtoupper($code) }}</a>
            @if (! $loop->last)
                <span>/</span>
            @endif
        @endforeach
    </nav>

    <div class="ia-grid">
        <div class="ia-art">
            <div class="ia-sprockets">
                @for ($i = 0; $i < 14; $i++) <span></span> @endfor
            </div>

            <div class="ia-aperture-wrap" aria-hidden="true">
                <svg viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="300" cy="300" r="220" stroke="#d98936" stroke-opacity="0.35" />
                    <circle cx="300" cy="300" r="90" stroke="#d98936" stroke-opacity="0.5" fill="#d98936" fill-opacity="0.04" />
                    <g stroke="#d98936" stroke-opacity="0.3">
                        <line x1="390" y1="300" x2="520" y2="300" />
                        <line x1="363.6" y1="236.4" x2="455.6" y2="144.4" />
                        <line x1="300" y1="210" x2="300" y2="80" />
                        <line x1="236.4" y1="236.4" x2="144.4" y2="144.4" />
                        <line x1="210" y1="300" x2="80" y2="300" />
                        <line x1="236.4" y1="363.6" x2="144.4" y2="455.6" />
                        <line x1="300" y1="390" x2="300" y2="520" />
                        <line x1="363.6" y1="363.6" x2="455.6" y2="455.6" />
                    </g>
                    <g stroke="#d98936" stroke-opacity="0.45">
                        <line x1="520" y1="300" x2="535" y2="300" />
                        <line x1="455.6" y1="144.4" x2="466.2" y2="133.8" />
                        <line x1="300" y1="80" x2="300" y2="65" />
                        <line x1="144.4" y1="144.4" x2="133.8" y2="133.8" />
                        <line x1="80" y1="300" x2="65" y2="300" />
                        <line x1="144.4" y1="455.6" x2="133.8" y2="466.2" />
                        <line x1="300" y1="520" x2="300" y2="535" />
                        <line x1="455.6" y1="455.6" x2="466.2" y2="466.2" />
                    </g>
                    <g fill="#9c8f7c" font-family="IBM Plex Mono, monospace" font-size="15" text-anchor="middle">
                        <text x="300" y="30">f/1.4</text>
                        <text x="112.6" y="107.6">f/2</text>
                        <text x="25" y="305">f/2.8</text>
                        <text x="112.6" y="492.4">f/4</text>
                        <text x="300" y="580">f/5.6</text>
                        <text x="487.4" y="492.4">f/8</text>
                        <text x="575" y="305">f/11</text>
                        <text x="487.4" y="107.6">f/16</text>
                    </g>
                </svg>
            </div>

            <div class="ia-rise ia-d1">
                <span class="ia-eyebrow">{{ $eyebrow ?? __('institution.eyebrow') }}</span>
            </div>
            <h1 class="ia-headline ia-rise ia-d2">{{ $heading }}</h1>
            <p class="ia-sub ia-rise ia-d3">{{ $subheading }}</p>
        </div>

        <div class="ia-form-col">
            <div class="ia-card ia-rise ia-d2">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
