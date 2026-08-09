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
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .ia-shell {
            /* Palet: almanak.tfsf.org.tr ile tutarlı — lacivert-siyah zemin, altın vurgu. */
            --ia-bg: #0f111a;
            --ia-bg-soft: #14161f;
            --ia-surface: rgba(255, 255, 255, .04);
            --ia-surface-border: rgba(255, 255, 255, .08);
            --ia-copper: #c9a84c;
            --ia-copper-bright: #ddc178;
            --ia-cream: #e6e6e6;
            --ia-muted: #c0c0c0;
            --ia-muted-dim: #7a7a8c;
            --ia-red: #e0857a;
            --ia-focus: rgba(201, 168, 76, .28);
            min-height: 100vh;
            background:
                radial-gradient(ellipse 900px 700px at 82% -10%, rgba(201,168,76,.08), transparent 60%),
                radial-gradient(ellipse 1200px 900px at 50% 120%, rgba(0,0,0,.5), transparent 60%),
                var(--ia-bg);
            color: var(--ia-muted);
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        .ia-noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .04;
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
            gap: .35rem;
            font-family: 'Figtree', sans-serif;
            font-size: .78rem;
            font-weight: 600;
        }
        .ia-lang a {
            color: var(--ia-muted-dim);
            text-decoration: none;
            padding: .3rem .6rem;
            border-radius: 6px;
            border: 1px solid transparent;
            transition: color .15s ease, border-color .15s ease, background-color .15s ease;
        }
        .ia-lang a:hover { color: var(--ia-muted); }
        .ia-lang a.is-active {
            color: var(--ia-copper);
            background: rgba(201, 168, 76, .08);
            border-color: rgba(201, 168, 76, .35);
        }
        .ia-lang span { display: none; }

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
            opacity: .5;
        }
        @media (max-width: 1023px) {
            .ia-aperture-wrap { right: -30%; width: 460px; height: 460px; opacity: .35; }
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
            font-family: 'Figtree', sans-serif;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--ia-copper);
            display: inline-flex;
            align-items: center;
            gap: .6em;
        }

        .ia-headline {
            font-family: 'Figtree', sans-serif;
            font-weight: 800;
            font-size: clamp(2.4rem, 5vw, 3.6rem);
            line-height: 1.08;
            letter-spacing: -.01em;
            color: var(--ia-cream);
            margin-top: 1.1rem;
            max-width: 14ch;
        }
        .ia-headline em {
            font-style: normal;
            color: var(--ia-copper);
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
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            margin-bottom: .5rem;
        }

        .ia-card-title {
            font-family: 'Figtree', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--ia-cream);
            margin-bottom: 2rem;
        }

        .ia-field { margin-bottom: 1.35rem; }

        .ia-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--ia-muted);
            margin-bottom: .5rem;
        }

        .ia-input {
            width: 100%;
            background: var(--ia-surface);
            border: 1px solid var(--ia-surface-border);
            border-radius: 8px;
            padding: .72rem .85rem;
            color: var(--ia-cream);
            font-family: 'Figtree', sans-serif;
            font-size: .92rem;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .ia-input::placeholder { color: var(--ia-muted-dim); }
        .ia-input:focus {
            outline: none;
            border-color: var(--ia-copper);
            background: rgba(255, 255, 255, .06);
            box-shadow: 0 0 0 3px var(--ia-focus);
        }

        .ia-error {
            margin-top: .5rem;
            font-size: .8rem;
            color: var(--ia-red);
        }

        .ia-status {
            margin-bottom: 1.5rem;
            padding: .75rem .9rem;
            border: 1px solid rgba(120, 190, 130, .3);
            background: rgba(88, 140, 92, .1);
            color: #a9d2ac;
            font-size: .85rem;
            border-radius: 8px;
        }

        .ia-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0 1.75rem;
            font-size: .85rem;
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
            width: 16px;
            height: 16px;
            border: 1px solid var(--ia-surface-border);
            background: var(--ia-surface);
            border-radius: 4px;
            display: inline-grid;
            place-content: center;
            cursor: pointer;
        }
        .ia-check input::before {
            content: '';
            width: 8px;
            height: 8px;
            transform: scale(0);
            border-radius: 2px;
            background: var(--ia-copper);
            transition: transform .1s ease-in-out;
        }
        .ia-check input:checked::before { transform: scale(1); }

        .ia-link {
            color: var(--ia-copper);
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px solid transparent;
            transition: border-color .15s ease, color .15s ease;
        }
        .ia-link:hover { color: var(--ia-copper-bright); border-color: var(--ia-copper-bright); }

        .ia-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: var(--ia-copper);
            color: #14161f;
            font-family: 'Figtree', sans-serif;
            font-weight: 700;
            font-size: .92rem;
            padding: .8rem 1rem;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            transition: background-color .15s ease, transform .1s ease;
        }
        .ia-btn:hover { background: var(--ia-copper-bright); }
        .ia-btn:active { transform: translateY(1px); }

        .ia-foot {
            margin-top: 2.25rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--ia-surface-border);
            font-size: .8rem;
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

    <nav class="ia-lang" aria-label="{{ __('juri.eyebrow') }}">
        @foreach (config('locales.supported') as $code => $label)
            <a href="{{ route('juri.language', $code) }}" class="{{ app()->getLocale() === $code ? 'is-active' : '' }}">{{ strtoupper($code) }}</a>
        @endforeach
    </nav>

    <div class="ia-grid">
        <div class="ia-art">
            <div class="ia-sprockets">
                @for ($i = 0; $i < 14; $i++) <span></span> @endfor
            </div>

            <div class="ia-aperture-wrap" aria-hidden="true">
                <svg viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="300" cy="300" r="220" stroke="#c9a84c" stroke-opacity="0.3" />
                    <circle cx="300" cy="300" r="90" stroke="#c9a84c" stroke-opacity="0.45" fill="#c9a84c" fill-opacity="0.04" />
                    <g stroke="#c9a84c" stroke-opacity="0.25">
                        <line x1="390" y1="300" x2="520" y2="300" />
                        <line x1="363.6" y1="236.4" x2="455.6" y2="144.4" />
                        <line x1="300" y1="210" x2="300" y2="80" />
                        <line x1="236.4" y1="236.4" x2="144.4" y2="144.4" />
                        <line x1="210" y1="300" x2="80" y2="300" />
                        <line x1="236.4" y1="363.6" x2="144.4" y2="455.6" />
                        <line x1="300" y1="390" x2="300" y2="520" />
                        <line x1="363.6" y1="363.6" x2="455.6" y2="455.6" />
                    </g>
                    <g stroke="#c9a84c" stroke-opacity="0.4">
                        <line x1="520" y1="300" x2="535" y2="300" />
                        <line x1="455.6" y1="144.4" x2="466.2" y2="133.8" />
                        <line x1="300" y1="80" x2="300" y2="65" />
                        <line x1="144.4" y1="144.4" x2="133.8" y2="133.8" />
                        <line x1="80" y1="300" x2="65" y2="300" />
                        <line x1="144.4" y1="455.6" x2="133.8" y2="466.2" />
                        <line x1="300" y1="520" x2="300" y2="535" />
                        <line x1="455.6" y1="455.6" x2="466.2" y2="466.2" />
                    </g>
                    <g fill="#7a7a8c" font-family="Figtree, sans-serif" font-size="15" font-weight="600" text-anchor="middle">
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
                <span class="ia-eyebrow">{{ $eyebrow ?? __('juri.eyebrow') }}</span>
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
