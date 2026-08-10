@props(['title' => null])
@php
    $member = auth()->user();
    $displayName = trim(($member->first_name ?? '').' '.($member->last_name ?? '')) ?: $member->email;
    $initials = $member->first_name
        ? strtoupper(mb_substr($member->first_name, 0, 1).mb_substr($member->last_name ?? '', 0, 1))
        : strtoupper(mb_substr($member->email, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="ip-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? __('uye.eyebrow') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        /**
         * Uygulama genelinde tek tip onay modalı + toast bildirim mimarisi
         * (bkz. EYS app-layout.blade.php — aynı desen).
         * window.uyeConfirm(message, target) — target bir <form> ise onaylanınca
         * gönderilir, fonksiyon ise onaylanınca çağrılır.
         * window.uyeToast(type, message) — type: success|error|info.
         */
        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                items: [],
                push(type, message) {
                    if (!message) return;
                    const id = Date.now() + Math.random();
                    this.items.push({ id, type, message });
                    setTimeout(() => {
                        this.items = this.items.filter((t) => t.id !== id);
                    }, 4000);
                },
            });

            Alpine.store('confirmModal', {
                open: false,
                message: '',
                onConfirm: null,
                show(message, onConfirm) {
                    this.message = message;
                    this.onConfirm = onConfirm;
                    this.open = true;
                },
                async confirm() {
                    const fn = this.onConfirm;
                    this.open = false;
                    this.onConfirm = null;
                    if (fn) await fn();
                },
                cancel() {
                    this.open = false;
                    this.onConfirm = null;
                    window.uyeToast('info', @js(__('uye.common.action_cancelled')));
                },
            });
        });

        window.uyeToast = (type, message) => Alpine.store('toast').push(type, message);

        window.uyeConfirm = (message, target) => {
            Alpine.store('confirmModal').show(message, () => {
                if (target instanceof HTMLFormElement) {
                    target.requestSubmit();
                } else if (typeof target === 'function') {
                    return target();
                }
            });
        };
    </script>

    <style>
        [x-cloak] { display: none !important; }

        .ip-shell {
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
            --ia-focus: rgba(201, 168, 76, .28);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 264px 1fr;
            background: var(--ia-bg);
            color: var(--ia-muted);
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
        }
        @media (max-width: 900px) {
            .ip-shell { grid-template-columns: 1fr; }
        }

        /* ---- Sidebar ---- */
        .ip-sidebar {
            background: linear-gradient(180deg, var(--ia-bg-soft), var(--ia-bg));
            border-right: 1px solid var(--ia-surface-border);
            display: flex;
            flex-direction: column;
            padding: 1.75rem 1.25rem;
        }
        @media (max-width: 900px) { .ip-sidebar { display: none; } }

        .ip-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--ia-surface-border);
        }
        .ip-brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid var(--ia-copper);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .ip-brand-mark span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid var(--ia-copper-bright);
        }
        .ip-brand-text .ip-brand-eyebrow {
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            display: block;
        }
        .ip-brand-text .ip-brand-title {
            font-family: 'Figtree', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--ia-cream);
            display: block;
            line-height: 1.2;
        }

        .ip-nav { display: flex; flex-direction: column; gap: .2rem; }
        .ip-nav-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .6rem .7rem;
            border-radius: 8px;
            color: var(--ia-muted);
            text-decoration: none;
            font-size: .86rem;
            font-weight: 600;
            border-left: 2px solid transparent;
            transition: background-color .15s ease, color .15s ease;
        }
        .ip-nav-item svg { width: 17px; height: 17px; flex-shrink: 0; opacity: .85; }
        .ip-nav-item:hover { color: var(--ia-cream); background: rgba(201,168,76,.06); }
        .ip-nav-item.is-active {
            color: var(--ia-copper);
            background: rgba(201,168,76,.1);
            border-left-color: var(--ia-copper);
        }
        .ip-nav-item.is-disabled { cursor: default; color: var(--ia-muted-dim); }
        .ip-nav-item.is-disabled:hover { background: none; color: var(--ia-muted-dim); }

        /* ---- Aç/kapa alt menü (ör. Üye Bilgilerim) ---- */
        .ip-nav-group-btn {
            display: flex;
            align-items: center;
            gap: .7rem;
            width: 100%;
            padding: .6rem .7rem;
            border-radius: 8px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ia-muted);
            font-family: 'Figtree', sans-serif;
            font-size: .86rem;
            font-weight: 600;
            text-align: left;
            transition: background-color .15s ease, color .15s ease;
        }
        .ip-nav-group-btn:hover { color: var(--ia-cream); background: rgba(201,168,76,.06); }
        .ip-nav-group-btn svg:first-child { width: 17px; height: 17px; flex-shrink: 0; opacity: .85; }
        .ip-nav-group-btn.is-active { color: var(--ia-copper); }
        .ip-nav-group-label { flex: 1; }
        .ip-nav-group-chevron { width: 14px !important; height: 14px !important; opacity: .55; transition: transform .15s ease; }
        .ip-nav-group-btn[aria-expanded="true"] .ip-nav-group-chevron { transform: rotate(90deg); }

        .ip-nav-group-body {
            margin: .2rem 0 .35rem 1.15rem;
            padding-left: .75rem;
            border-left: 1px solid var(--ia-surface-border);
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }
        .ip-nav-group-body .ip-nav-item { font-size: .82rem; padding: .45rem .6rem; }

        .ip-nav-soon {
            margin-left: auto;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            background: rgba(255,255,255,.06);
            border: 1px solid var(--ia-surface-border);
            border-radius: 999px;
            padding: .1rem .45rem;
        }

        .ip-sidebar-foot {
            margin-top: auto;
            padding-top: 1.5rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .06em;
            color: var(--ia-muted-dim);
        }

        /* ---- Main column ---- */
        .ip-main { display: flex; flex-direction: column; min-width: 0; }

        .ip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.75rem;
            border-bottom: 1px solid var(--ia-surface-border);
            background: rgba(15, 17, 26, .7);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(6px);
        }
        .ip-header-title {
            font-family: 'Figtree', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--ia-cream);
        }
        .ip-header-right { display: flex; align-items: center; gap: 1.25rem; }

        .ip-lang { display: flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600; }
        .ip-lang a {
            color: var(--ia-muted-dim);
            text-decoration: none;
            padding: .3rem .6rem;
            border-radius: 6px;
            border: 1px solid transparent;
            transition: color .15s ease, border-color .15s ease, background-color .15s ease;
        }
        .ip-lang a:hover { color: var(--ia-muted); }
        .ip-lang a.is-active {
            color: var(--ia-copper);
            background: rgba(201, 168, 76, .08);
            border-color: rgba(201, 168, 76, .35);
        }
        .ip-lang span { display: none; }

        .ip-user-btn {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: none;
            border: 1px solid transparent;
            padding: .35rem .5rem .35rem .35rem;
            border-radius: 999px;
            cursor: pointer;
            color: var(--ia-cream);
            font-family: 'Figtree', sans-serif;
            transition: border-color .15s ease, background-color .15s ease;
        }
        .ip-user-btn:hover, .ip-user-btn[aria-expanded="true"] {
            border-color: var(--ia-surface-border);
            background: var(--ia-surface);
        }
        .ip-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--ia-copper);
            color: #14161f;
            font-size: .72rem;
            font-weight: 700;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .ip-user-btn-name { font-size: .84rem; font-weight: 600; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ip-user-btn svg { width: 13px; height: 13px; opacity: .6; transition: transform .15s ease; }
        .ip-user-btn[aria-expanded="true"] svg { transform: rotate(180deg); }

        .ip-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + .5rem);
            width: 230px;
            background: #14161f;
            border: 1px solid var(--ia-surface-border);
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(0,0,0,.5);
            overflow: hidden;
            z-index: 30;
        }
        .ip-dropdown-email {
            padding: .8rem .9rem;
            font-size: .78rem;
            color: var(--ia-muted);
            border-bottom: 1px solid var(--ia-surface-border);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .ip-dropdown-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            width: 100%;
            text-align: left;
            padding: .7rem .9rem;
            font-size: .84rem;
            font-weight: 600;
            color: var(--ia-cream);
            background: none;
            border: none;
            text-decoration: none;
            cursor: pointer;
            font-family: 'Figtree', sans-serif;
        }
        .ip-dropdown-item svg { width: 15px; height: 15px; opacity: .7; }
        .ip-dropdown-item:hover { background: rgba(201,168,76,.08); color: var(--ia-copper); }
        .ip-dropdown-item.is-danger:hover { background: rgba(224,133,122,.12); color: #e0857a; }

        .ip-content { padding: 1.75rem; flex: 1; }

        .ip-panel-stack { display: flex; flex-direction: column; gap: 1.5rem; }

        .ip-card {
            background: var(--ia-surface);
            border: 1px solid var(--ia-surface-border);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .ip-status {
            margin-bottom: 1.25rem;
            padding: .75rem .9rem;
            border: 1px solid rgba(120, 190, 130, .3);
            background: rgba(88, 140, 92, .1);
            color: #a9d2ac;
            font-size: .85rem;
            border-radius: 8px;
        }

        .ip-section-title {
            font-family: 'Figtree', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--ia-cream);
            margin-bottom: .3rem;
        }
        .ip-section-hint {
            font-size: .82rem;
            color: var(--ia-muted);
            margin-bottom: 1.4rem;
        }

        /* ---- Form bileşenleri (x-uye.input/label/button/input-error) ---- */
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
            background: var(--ia-bg);
            border: 1px solid var(--ia-surface-border);
            border-radius: 8px;
            padding: .72rem .85rem;
            color: var(--ia-cream);
            font-family: 'Figtree', sans-serif;
            font-size: .92rem;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        textarea.ia-input { resize: vertical; min-height: 5rem; }
        .ia-input::placeholder { color: var(--ia-muted-dim); }
        .ia-input:focus {
            outline: none;
            border-color: var(--ia-copper);
            box-shadow: 0 0 0 3px var(--ia-focus);
        }
        .ia-error { margin-top: .5rem; font-size: .8rem; color: #e0857a; }

        /* ---- Flatpickr (ör. Doğum Tarihi) — koyu tema override ---- */
        .flatpickr-calendar {
            background: var(--ia-bg-soft);
            border: 1px solid var(--ia-surface-border);
            border-radius: 12px;
            box-shadow: 0 20px 45px rgba(0,0,0,.45);
            font-family: 'Figtree', sans-serif;
        }
        .flatpickr-calendar.arrowTop:before { border-bottom-color: var(--ia-surface-border); }
        .flatpickr-calendar.arrowTop:after { border-bottom-color: var(--ia-bg-soft); }
        .flatpickr-months { padding: .85rem .85rem 0; }
        .flatpickr-month { color: var(--ia-cream) !important; fill: var(--ia-cream) !important; height: auto; }
        .flatpickr-current-month { font-size: .95rem; padding: 0; }
        .flatpickr-current-month .cur-month { color: var(--ia-cream) !important; font-weight: 700; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { color: var(--ia-cream) !important; background: transparent; font-weight: 700; }
        .flatpickr-current-month .flatpickr-monthDropdown-months option { background: var(--ia-bg-soft); color: var(--ia-cream); }
        .flatpickr-current-month input.cur-year { color: var(--ia-cream) !important; background: transparent; font-weight: 700; }
        .flatpickr-prev-month, .flatpickr-next-month { color: var(--ia-muted) !important; fill: var(--ia-muted) !important; }
        .flatpickr-prev-month:hover, .flatpickr-next-month:hover { color: var(--ia-copper) !important; fill: var(--ia-copper) !important; }
        .flatpickr-weekdays { background: transparent; margin-top: .5rem; }
        span.flatpickr-weekday { background: transparent; color: var(--ia-muted-dim); font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        .flatpickr-days { border: none; }
        .dayContainer { padding: 0 .5rem .75rem; }
        .flatpickr-day { color: var(--ia-cream); border-radius: 7px; }
        .flatpickr-day.today { border-color: var(--ia-copper); }
        .flatpickr-day:hover, .flatpickr-day:focus { background: rgba(201,168,76,.14); border-color: transparent; }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: var(--ia-copper);
            border-color: var(--ia-copper);
            color: #14161f;
        }
        .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay { color: var(--ia-muted-dim); }
        .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover { color: var(--ia-muted-dim); opacity: .35; cursor: not-allowed; background: none; }
        .numInputWrapper span.arrowUp:after { border-bottom-color: var(--ia-muted); }
        .numInputWrapper span.arrowDown:after { border-top-color: var(--ia-muted); }
        .numInputWrapper:hover { background: rgba(255,255,255,.04); }
        .ia-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: var(--ia-copper);
            color: #14161f;
            font-family: 'Figtree', sans-serif;
            font-weight: 700;
            font-size: .92rem;
            padding: .8rem 1.4rem;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            transition: background-color .15s ease, transform .1s ease;
        }
        .ia-btn:hover { background: var(--ia-copper-bright); }
        .ia-btn:active { transform: translateY(1px); }
        .ia-btn-secondary {
            background: transparent;
            color: var(--ia-muted);
            border: 1px solid var(--ia-surface-border);
        }
        .ia-btn-secondary:hover { background: rgba(201,168,76,.06); color: var(--ia-cream); border-color: var(--ia-copper); }
        .ia-btn-secondary svg { width: 14px; height: 14px; }
        .ia-btn-danger {
            background: #e0857a;
            color: #2a1512;
        }
        .ia-btn-danger:hover { background: #e89b91; }
        .ia-btn-danger svg { width: 15px; height: 15px; }
        .ia-btn.ip-btn-sm { padding: .55rem 1.1rem; font-size: .82rem; text-decoration: none; }

        .ip-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 640px) { .ip-grid-2 { grid-template-columns: 1fr; } }

        /* ---- Onay modalı — hesap silme gibi geri alınamaz işlemler öncesi. ---- */
        .ip-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 7, 12, .72);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1.5rem;
        }
        .ip-modal {
            background: var(--ia-bg-soft);
            border: 1px solid var(--ia-surface-border);
            border-radius: 14px;
            padding: 1.75rem;
            width: 100%;
            max-width: 26rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }
        .ip-modal-message {
            color: var(--ia-muted);
            font-size: .88rem;
            line-height: 1.5;
            margin-bottom: 1.25rem;
        }
        .ip-modal-actions { display: flex; justify-content: flex-end; gap: .75rem; margin-top: 1.5rem; }

        /* ---- Toast bildirimleri — bottom-center, silme/kaydetme sonrası. ---- */
        .ip-toast-stack {
            position: fixed;
            left: 50%;
            bottom: 1.5rem;
            transform: translateX(-50%);
            z-index: 1100;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
            pointer-events: none;
        }
        .ip-toast {
            pointer-events: auto;
            min-width: 16rem;
            max-width: 26rem;
            background: var(--ia-bg-soft);
            border: 1px solid var(--ia-surface-border);
            border-radius: 10px;
            padding: .75rem 1.1rem;
            font-size: .85rem;
            color: var(--ia-cream);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
        }
        .ip-toast.is-success { border-color: rgba(120, 190, 130, .35); background: rgba(88, 140, 92, .16); color: #c8e8ca; }
        .ip-toast.is-error { border-color: rgba(224, 133, 122, .4); background: rgba(224, 133, 122, .14); color: #f0c2ba; }
        .ip-toast.is-info { border-color: var(--ia-surface-border); background: var(--ia-surface); color: var(--ia-muted); }

        /* ---- Liste satırı ikon butonu (düzenle/sil) ---- */
        .ip-row-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: var(--ia-muted);
            border: 1px solid transparent;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }
        .ip-row-icon-btn svg { width: 16px; height: 16px; }
        .ip-row-icon-btn:hover { color: var(--ia-copper); background: rgba(201,168,76,.08); border-color: rgba(201,168,76,.25); }

        /* ---- Uyarı bandı ---- */
        .ip-alert { display: flex; gap: .85rem; padding: 1rem 1.1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .ip-alert svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: .1rem; }
        .ip-alert-warning { background: rgba(224,178,122,.1); border: 1px solid rgba(224,178,122,.3); color: #e6c896; }
        .ip-alert-title { font-family: 'Figtree', sans-serif; font-weight: 700; font-size: .9rem; margin-bottom: .2rem; color: var(--ia-cream); }
        .ip-alert-text { font-size: .84rem; color: var(--ia-muted); }
        .ip-alert-text a { color: var(--ia-copper); font-weight: 600; text-decoration: none; }
        .ip-alert-text a:hover { color: var(--ia-copper-bright); }

        /* ---- Rozet (ör. EXIF Eksik) ---- */
        .ip-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
        }
        .ip-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .ip-badge.is-warning { color: #d9a441; background: rgba(217,164,65,.14); }

        /* ---- Ekleme/düzenleme alt sayfaları — başlık satırı + geri dön ---- */
        .ip-page-actions { display: flex; justify-content: flex-end; margin-bottom: 1.25rem; }

        /* ---- Fotoğraf Portfolyosu ---- */
        .ip-view-toggle { display: inline-flex; border: 1px solid var(--ia-surface-border); border-radius: 7px; overflow: hidden; }
        .ip-view-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.1rem;
            height: 2.1rem;
            background: transparent;
            border: none;
            color: var(--ia-muted-dim);
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }
        .ip-view-toggle-btn:not(:last-child) { border-right: 1px solid var(--ia-surface-border); }
        .ip-view-toggle-btn svg { width: 16px; height: 16px; }
        .ip-view-toggle-btn:hover { color: var(--ia-cream); }
        .ip-view-toggle-btn.is-active { background: rgba(201,168,76,.12); color: var(--ia-copper); }

        .ip-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }
        .ip-photo-card {
            background: var(--ia-surface);
            border: 1px solid var(--ia-surface-border);
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            transition: transform .15s ease, border-color .15s ease;
            text-align: left;
            text-decoration: none;
            padding: 0;
            display: block;
            width: 100%;
        }
        .ip-photo-card:hover { transform: translateY(-2px); border-color: var(--ia-copper); }
        .ip-photo-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; background: var(--ia-bg); }
        .ip-photo-card .ip-photo-meta { padding: .7rem .8rem; }
        .ip-photo-card .ip-photo-title { color: var(--ia-cream); font-weight: 600; font-size: .88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ip-photo-card .ip-photo-sub { color: var(--ia-muted-dim); font-size: .76rem; margin-top: .2rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }

        .ip-table-wrap { overflow-x: auto; border: 1px solid var(--ia-surface-border); border-radius: 10px; }
        .ip-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
        .ip-table th {
            text-align: left;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            padding: .8rem 1rem;
            border-bottom: 1px solid var(--ia-surface-border);
            background: rgba(255,255,255,.02);
        }
        .ip-table td { padding: .7rem 1rem; border-bottom: 1px solid var(--ia-surface-border); color: var(--ia-muted); vertical-align: middle; }
        .ip-table tr:last-child td { border-bottom: none; }
        .ip-table tr { cursor: pointer; transition: background-color .15s ease; }
        .ip-table tbody tr:hover { background: rgba(201,168,76,.05); }
        .ip-table td.ip-cell-name { color: var(--ia-cream); font-weight: 600; }
        .ip-table td.ip-cell-thumb { width: 56px; }
        .ip-table td.ip-cell-thumb img { width: 42px; height: 42px; object-fit: cover; border-radius: 6px; display: block; background: var(--ia-bg); }
        .ip-table-empty { padding: 2.5rem 1rem; text-align: center; color: var(--ia-muted-dim); font-size: .88rem; }

        .ip-modal.is-wide { max-width: 42rem; max-height: 90vh; overflow-y: auto; }

        .ip-exif-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem 1.2rem; font-size: .84rem; }
        .ip-exif-grid dt { color: var(--ia-muted-dim); font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; }
        .ip-exif-grid dd { color: var(--ia-muted); margin: 0 0 .6rem; }

        /* ---- Ekipman seçim listesi (fotoğraf yükleme/düzenleme) ---- */
        .ip-checklist { display: flex; flex-direction: column; gap: .55rem; max-height: 9rem; overflow-y: auto; }
        .ip-checklist-item { display: flex; align-items: center; gap: .55rem; font-size: .85rem; color: var(--ia-muted); cursor: pointer; }
        .ip-checklist-item input[type="checkbox"] { width: 15px; height: 15px; accent-color: var(--ia-copper); flex-shrink: 0; }

        /* ---- Fotoğraf detayında kullanılan ekipman listesi ---- */
        .ip-tag-list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .ip-tag { display: inline-flex; align-items: center; padding: .3rem .7rem; border-radius: 999px; background: rgba(201,168,76,.1); color: var(--ia-cream); font-size: .78rem; }
    </style>
</head>
<body class="ip-shell">
    <aside class="ip-sidebar">
        <a href="{{ route('dashboard') }}" class="ip-brand">
            <span class="ip-brand-mark"><span></span></span>
            <span class="ip-brand-text">
                <span class="ip-brand-eyebrow">TFSF</span>
                <span class="ip-brand-title">{{ __('uye.login.heading') }}</span>
            </span>
        </a>

        <nav class="ip-nav">
            <a href="{{ route('dashboard') }}" class="ip-nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <x-uye.icon name="dashboard" />
                {{ __('uye.nav.dashboard') }}
            </a>
            <a href="{{ route('portfolio.index') }}" class="ip-nav-item {{ request()->routeIs('portfolio.*') ? 'is-active' : '' }}">
                <x-uye.icon name="camera" />
                {{ __('uye.nav.portfolio') }}
            </a>
            <a href="{{ route('equipment.index') }}" class="ip-nav-item {{ request()->routeIs('equipment.*') ? 'is-active' : '' }}">
                <x-uye.icon name="equipment" />
                {{ __('uye.nav.equipment') }}
            </a>
            <div x-data="{ o: {{ request()->routeIs('profile.*') ? 'true' : 'false' }} }">
                <button type="button" class="ip-nav-group-btn {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" @click="o = !o" :aria-expanded="o.toString()">
                    <x-uye.icon name="account" />
                    <span class="ip-nav-group-label">{{ __('uye.nav.profile') }}</span>
                    <x-uye.icon name="chevron-right" class="ip-nav-group-chevron" />
                </button>
                <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                    <a href="{{ route('profile.edit') }}" class="ip-nav-item {{ request()->routeIs('profile.edit') ? 'is-active' : '' }}">
                        {{ __('uye.nav.profile') }}
                    </a>
                    <span class="ip-nav-item is-disabled">
                        {{ __('uye.nav.privacy') }}
                        <span class="ip-nav-soon">{{ __('uye.nav.soon') }}</span>
                    </span>
                    <a href="{{ route('profile.password.edit') }}" class="ip-nav-item {{ request()->routeIs('profile.password.*') ? 'is-active' : '' }}">
                        {{ __('uye.nav.password') }}
                    </a>
                    <a href="{{ route('profile.account.edit') }}" class="ip-nav-item {{ request()->routeIs('profile.account.*') ? 'is-active' : '' }}">
                        {{ __('uye.nav.account') }}
                    </a>
                </div>
            </div>
        </nav>

        <div class="ip-sidebar-foot">TFSF · v{{ config('app.version', '0.1') }}</div>
    </aside>

    <div class="ip-main">
        <header class="ip-header">
            <div class="ip-header-title">{{ $title ?? '' }}</div>

            <div class="ip-header-right">
                <nav class="ip-lang" aria-label="{{ __('uye.eyebrow') }}">
                    @foreach (config('locales.supported') as $code => $label)
                        <a href="{{ route('language', $code) }}" class="{{ app()->getLocale() === $code ? 'is-active' : '' }}">{{ strtoupper($code) }}</a>
                    @endforeach
                </nav>

                <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                    <button type="button" class="ip-user-btn" @click="open = !open" :aria-expanded="open.toString()">
                        <span class="ip-avatar">{{ $initials }}</span>
                        <span class="ip-user-btn-name">{{ $displayName }}</span>
                        <x-uye.icon name="chevron-down" />
                    </button>

                    <div class="ip-dropdown" x-show="open" x-cloak x-transition>
                        <div class="ip-dropdown-email">{{ $member->email }}</div>

                        <a href="{{ route('profile.edit') }}" class="ip-dropdown-item">
                            <x-uye.icon name="account" />
                            {{ __('uye.nav.profile') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="ip-dropdown-item is-danger">
                                <x-uye.icon name="logout" />
                                {{ __('uye.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="ip-content">
            @if (session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent'], true) && ! request()->routeIs('equipment.*') && ! request()->routeIs('portfolio.*'))
                <div class="ip-status">{{ session('status') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <div x-data class="ip-modal-overlay" x-show="$store.confirmModal.open" x-cloak x-transition.opacity>
        <div class="ip-modal" role="alertdialog" aria-modal="true">
            <p class="ip-modal-message" x-text="$store.confirmModal.message"></p>
            <div class="ip-modal-actions">
                <button type="button" class="ia-btn ia-btn-secondary" @click="$store.confirmModal.cancel()">{{ __('uye.common.cancel') }}</button>
                <button type="button" class="ia-btn" @click="$store.confirmModal.confirm()">{{ __('uye.common.confirm') }}</button>
            </div>
        </div>
    </div>

    <div x-data class="ip-toast-stack">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="ip-toast" :class="'is-' + t.type" x-text="t.message" x-transition></div>
        </template>
    </div>
</body>
</html>
