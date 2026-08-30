@props(['title' => null])
@php
    $juror = auth('juri')->user();
    $displayName = trim(($juror->first_name ?? '').' '.($juror->last_name ?? '')) ?: $juror->email;
    $initials = $juror->first_name
        ? strtoupper(mb_substr($juror->first_name, 0, 1).mb_substr($juror->last_name ?? '', 0, 1))
        : strtoupper(mb_substr($juror->email, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="ip-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? __('juri.eyebrow') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

        /* ---- Aç/kapa alt menü (ör. Jüri Bilgileri) ---- */
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

        /* ---- Form bileşenleri (x-juri.input/label/button/input-error) ---- */
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

        .ip-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 640px) { .ip-grid-2 { grid-template-columns: 1fr; } }

        /* ---- Liste araç çubuğu / tablo / rozet (ör. Yetkili Bilgileri) ---- */
        .ip-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .ip-toolbar-title { font-family: 'Figtree', sans-serif; font-weight: 800; font-size: 1.05rem; color: var(--ia-cream); }
        .ip-toolbar-hint { font-size: .82rem; color: var(--ia-muted); margin-top: .2rem; }
        .ia-btn.ip-btn-sm { padding: .55rem 1.1rem; font-size: .82rem; text-decoration: none; }

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
        .ip-table td { padding: .85rem 1rem; border-bottom: 1px solid var(--ia-surface-border); color: var(--ia-muted); vertical-align: middle; }
        .ip-table tr:last-child td { border-bottom: none; }
        .ip-table td.ip-cell-name { color: var(--ia-cream); font-weight: 600; }
        .ip-table-empty { padding: 2.5rem 1rem; text-align: center; color: var(--ia-muted-dim); font-size: .88rem; }

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
        .ip-badge.is-active { color: #8fcf93; background: rgba(88,140,92,.14); }
        .ip-badge.is-inactive { color: #e0857a; background: rgba(224,133,122,.12); }
        .ip-badge.is-draft { color: #b5b5c3; background: rgba(181,181,195,.12); }
        .ip-badge.is-submitted, .ip-badge.is-under-review { color: #8ec4e6; background: rgba(91,155,194,.14); }
        .ip-badge.is-waiting-requirements, .ip-badge.is-needs-info { color: #e6c896; background: rgba(224,178,122,.13); }
        .ip-badge.is-approved { color: #8fcf93; background: rgba(88,140,92,.14); }
        .ip-badge.is-rejected { color: #e89a90; background: rgba(224,133,122,.12); }

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

        .ip-pagination { margin-top: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .ip-pagination-info { font-size: .78rem; color: var(--ia-muted-dim); }
        .ip-pagination-links { display: flex; align-items: center; gap: .3rem; }
        .ip-pagination-links a, .ip-pagination-links span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 .5rem;
            border-radius: 7px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--ia-muted);
            border: 1px solid transparent;
        }
        .ip-pagination-links a:hover { color: var(--ia-cream); background: rgba(201,168,76,.06); }
        .ip-pagination-links span.is-current { color: #14161f; background: var(--ia-copper); }
        .ip-pagination-links span.is-disabled { color: var(--ia-muted-dim); }

        .ip-page-actions { display: flex; justify-content: flex-end; margin-bottom: 1.25rem; }

        /* ---- On/Off switch (ör. Yetkili durumu) ---- */
        .ip-switch { display: inline-flex; align-items: center; gap: .65rem; cursor: pointer; user-select: none; }
        .ip-switch-checkbox { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
        .ip-switch-track {
            width: 40px;
            height: 22px;
            border-radius: 999px;
            background: var(--ia-bg);
            border: 1px solid var(--ia-surface-border);
            position: relative;
            flex-shrink: 0;
            transition: background-color .15s ease, border-color .15s ease;
        }
        .ip-switch-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--ia-muted-dim);
            transition: transform .15s ease, background-color .15s ease;
        }
        .ip-switch-checkbox:checked ~ .ip-switch-track { background: rgba(201,168,76,.18); border-color: var(--ia-copper); }
        .ip-switch-checkbox:checked ~ .ip-switch-track .ip-switch-thumb { transform: translateX(18px); background: var(--ia-copper); }
        .ip-switch-checkbox:focus-visible ~ .ip-switch-track { box-shadow: 0 0 0 3px var(--ia-focus); }
        .ip-switch-label { font-size: .92rem; font-weight: 600; color: var(--ia-cream); }

        /* ---- Uyarı bandı (ör. eksik kurum bilgisi) ---- */
        .ip-alert { display: flex; gap: .85rem; padding: 1rem 1.1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .ip-alert svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: .1rem; }
        .ip-alert-warning { background: rgba(224,178,122,.1); border: 1px solid rgba(224,178,122,.3); color: #e6c896; }
        .ip-alert-title { font-family: 'Figtree', sans-serif; font-weight: 700; font-size: .9rem; margin-bottom: .2rem; color: var(--ia-cream); }
        .ip-alert-text { font-size: .84rem; color: var(--ia-muted); }
        .ip-alert-text a { color: var(--ia-copper); font-weight: 600; text-decoration: none; }
        .ip-alert-text a:hover { color: var(--ia-copper-bright); }

        /* ---- İstatistik kartları (ör. Gösterge Paneli) ---- */
        .ip-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .ip-stat-card { display: flex; align-items: center; gap: .9rem; background: var(--ia-surface); border: 1px solid var(--ia-surface-border); border-radius: 12px; padding: 1.25rem 1.4rem; }
        .ip-stat-icon { width: 42px; height: 42px; border-radius: 10px; background: rgba(201,168,76,.1); color: var(--ia-copper); display: grid; place-items: center; flex-shrink: 0; }
        .ip-stat-icon svg { width: 20px; height: 20px; }
        .ip-stat-value { font-family: 'Figtree', sans-serif; font-weight: 800; font-size: 1.6rem; color: var(--ia-cream); line-height: 1; }
        .ip-stat-label { font-size: .78rem; color: var(--ia-muted); margin-top: .3rem; }

        /* ---- Jüri görevleri ---- */
        .ip-mobile-nav { display: none; }
        .jp-page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.2rem; }
        .jp-page-heading h1 { color: var(--ia-cream); font-size: 1.35rem; font-weight: 800; line-height: 1.25; text-wrap: balance; }
        .jp-page-heading p { max-width: 68ch; margin-top: .35rem; color: var(--ia-muted); font-size: .86rem; line-height: 1.55; text-wrap: pretty; }
        .jp-heading-action { flex-shrink: 0; text-decoration: none; padding: .65rem 1rem; font-size: .82rem; }
        .jp-section-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin: 1.65rem 0 .75rem; }
        .jp-section-heading h2 { color: var(--ia-cream); font-size: 1rem; font-weight: 800; }
        .jp-section-heading p { margin-top: .2rem; color: var(--ia-muted-dim); font-size: .78rem; }
        .jp-task-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem .7rem; margin-bottom: 1.5rem; padding: .8rem 1rem; border-top: 1px solid var(--ia-surface-border); border-bottom: 1px solid var(--ia-surface-border); color: var(--ia-muted); font-size: .8rem; }
        .jp-task-summary strong { color: var(--ia-cream); font-weight: 700; }
        .jp-task-list { overflow: hidden; border: 1px solid var(--ia-surface-border); border-radius: 12px; background: rgba(255,255,255,.018); }
        .jp-task-item { display: grid; grid-template-columns: minmax(0, 1fr) minmax(205px, 260px); gap: 1.5rem; padding: 1.25rem 1.35rem; border-bottom: 1px solid var(--ia-surface-border); }
        .jp-task-item:last-child { border-bottom: 0; }
        .jp-task-item:hover { background: rgba(255,255,255,.018); }
        .jp-task-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .jp-task-heading h3 { color: var(--ia-cream); font-size: .97rem; font-weight: 750; line-height: 1.35; text-wrap: pretty; }
        .jp-task-heading p { margin-top: .25rem; color: var(--ia-muted-dim); font-size: .77rem; }
        .jp-task-heading .ip-badge { flex-shrink: 0; }
        .jp-category-group { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .9rem; }
        .jp-category-tag { display: inline-flex; align-items: center; min-height: 1.65rem; padding: .25rem .55rem; border: 1px solid rgba(201,168,76,.22); border-radius: 6px; color: var(--ia-copper-bright); background: rgba(201,168,76,.06); font-size: .72rem; font-weight: 650; }
        .jp-task-note { max-width: 70ch; margin-top: .9rem; color: var(--ia-muted-dim); font-size: .76rem; line-height: 1.5; }
        .jp-task-dates { display: flex; flex-direction: column; justify-content: center; gap: .65rem; padding-left: 1.25rem; border-left: 1px solid var(--ia-surface-border); }
        .jp-task-dates div { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; }
        .jp-task-dates dt { color: var(--ia-muted-dim); font-size: .68rem; }
        .jp-task-dates dd { color: var(--ia-muted); font-size: .74rem; font-weight: 600; text-align: right; white-space: nowrap; }
        .jp-task-list.is-compact .jp-task-item { padding-block: 1rem; }
        .jp-task-empty { display: flex; align-items: center; justify-content: center; gap: 1rem; min-height: 190px; padding: 2rem; text-align: left; }
        .jp-task-empty-icon { display: grid; place-items: center; width: 42px; height: 42px; flex-shrink: 0; border-radius: 10px; background: rgba(201,168,76,.09); color: var(--ia-copper); }
        .jp-task-empty-icon svg { width: 21px; height: 21px; }
        .jp-task-empty h3 { color: var(--ia-cream); font-size: .92rem; font-weight: 750; }
        .jp-task-empty p { max-width: 52ch; margin-top: .25rem; color: var(--ia-muted-dim); font-size: .78rem; line-height: 1.5; }
        .jp-pagination { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; font-size: .78rem; }
        .jp-pagination a, .jp-pagination span { color: var(--ia-muted); text-decoration: none; }
        .jp-pagination a { font-weight: 700; }
        .jp-pagination a:hover { color: var(--ia-copper-bright); }
        .jp-pagination .is-disabled { color: var(--ia-muted-dim); }

        /* ---- Jüri görev detayı ---- */
        .jp-task-link { display: inline-flex; align-items: center; gap: .35rem; margin-top: 1rem; color: var(--ia-copper-bright); font-size: .78rem; font-weight: 750; text-decoration: none; }
        .jp-task-link:hover { color: var(--ia-cream); }
        .jp-detail-page { width: min(100%, 1440px); margin-inline: auto; }
        .jp-back-link { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: 1.15rem; color: var(--ia-muted); font-size: .78rem; font-weight: 650; text-decoration: none; }
        .jp-back-link:hover { color: var(--ia-copper-bright); }
        .jp-detail-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.15rem; }
        .jp-detail-title p { margin-bottom: .3rem; color: var(--ia-copper-bright); font-size: .74rem; font-weight: 700; letter-spacing: .03em; }
        .jp-detail-title h1 { max-width: 34ch; color: var(--ia-cream); font-size: clamp(1.45rem, 2.4vw, 2.15rem); font-weight: 800; line-height: 1.2; text-wrap: balance; }
        .jp-detail-hero .ip-badge { margin-top: .3rem; flex-shrink: 0; }
        .jp-readonly-note { display: flex; align-items: flex-start; gap: .7rem; margin-bottom: 1.25rem; padding: .75rem .9rem; border: 1px solid rgba(201,168,76,.2); border-radius: 8px; background: rgba(201,168,76,.045); color: var(--ia-muted); font-size: .77rem; line-height: 1.55; }
        .jp-readonly-note svg { width: 17px; height: 17px; margin-top: .05rem; flex: 0 0 auto; color: var(--ia-copper); }
        .jp-readonly-note strong { color: var(--ia-cream); }
        .jp-detail-facts { margin-bottom: 1.25rem; border-block: 1px solid var(--ia-surface-border); }
        .jp-detail-facts dl { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .jp-detail-facts dl > div { padding: .9rem 1rem; border-right: 1px solid var(--ia-surface-border); }
        .jp-detail-facts dl > div:first-child { padding-left: 0; }
        .jp-detail-facts dl > div:last-child { border-right: 0; }
        .jp-detail-facts dt { margin-bottom: .28rem; color: var(--ia-muted-dim); font-size: .67rem; }
        .jp-detail-facts dd { color: var(--ia-cream); font-size: .8rem; font-weight: 650; }
        .jp-detail-grid { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(270px, .7fr); align-items: start; gap: 1.25rem; margin-top: 1.25rem; }
        .jp-detail-aside { display: grid; gap: 1.25rem; }
        .jp-detail-section { overflow: hidden; border: 1px solid var(--ia-surface-border); border-radius: 10px; background: rgba(255,255,255,.018); }
        .jp-detail-section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--ia-surface-border); }
        .jp-detail-section-heading span { display: block; margin-bottom: .22rem; color: var(--ia-copper); font-size: .63rem; font-weight: 750; letter-spacing: .09em; text-transform: uppercase; }
        .jp-detail-section-heading h2 { color: var(--ia-cream); font-size: .98rem; font-weight: 780; line-height: 1.35; }
        .jp-detail-section-heading > strong { display: grid; place-items: center; min-width: 1.75rem; height: 1.75rem; border-radius: 6px; background: rgba(201,168,76,.1); color: var(--ia-copper-bright); font-size: .76rem; }
        .jp-prose-list > div { display: grid; grid-template-columns: minmax(130px, .25fr) minmax(0, 1fr); gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--ia-surface-border); }
        .jp-prose-list > div:last-child { border-bottom: 0; }
        .jp-prose-list dt { color: var(--ia-muted-dim); font-size: .72rem; font-weight: 650; }
        .jp-prose-list dd { max-width: 75ch; color: var(--ia-muted); font-size: .82rem; line-height: 1.65; white-space: pre-line; }
        .jp-category-detail { padding: 1.05rem 1.1rem; border-bottom: 1px solid var(--ia-surface-border); }
        .jp-category-detail:last-child { border-bottom: 0; }
        .jp-category-detail > h3 { color: var(--ia-cream); font-size: .9rem; font-weight: 750; }
        .jp-award-list { margin-top: .85rem; }
        .jp-award-list h4 { margin-bottom: .45rem; color: var(--ia-muted-dim); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .jp-award-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: .65rem 0; border-top: 1px solid rgba(255,255,255,.055); }
        .jp-award-row strong { color: var(--ia-muted); font-size: .78rem; font-weight: 700; }
        .jp-award-row p { max-width: 62ch; margin-top: .18rem; color: var(--ia-muted-dim); font-size: .72rem; line-height: 1.45; }
        .jp-award-row > span { flex: 0 0 auto; color: var(--ia-copper-bright); font-size: .7rem; font-weight: 700; }
        .jp-criterion-list { margin-top: 1rem; }
        .jp-criterion-list h4 { margin-bottom: .45rem; color: var(--ia-muted-dim); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .jp-criterion-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: start; gap: 1rem; padding: .7rem 0; border-top: 1px solid rgba(255,255,255,.055); }
        .jp-criterion-row strong { color: var(--ia-muted); font-size: .78rem; font-weight: 700; }
        .jp-criterion-row p { max-width: 62ch; margin-top: .18rem; color: var(--ia-muted-dim); font-size: .72rem; line-height: 1.45; }
        .jp-criterion-row dl { display: flex; gap: 1rem; }
        .jp-criterion-row dl div { min-width: 4.5rem; }
        .jp-criterion-row dt { color: var(--ia-muted-dim); font-size: .61rem; font-weight: 700; text-transform: uppercase; }
        .jp-criterion-row dd { margin-top: .15rem; color: var(--ia-copper-bright); font-size: .75rem; font-weight: 750; }
        .jp-inline-empty { padding-top: .55rem; border-top: 1px solid rgba(255,255,255,.055); color: var(--ia-muted-dim); font-size: .74rem; }
        .jp-schedule-list { padding: .35rem 1.1rem; }
        .jp-schedule-list > div { display: flex; justify-content: space-between; gap: 1rem; padding: .72rem 0; border-bottom: 1px solid rgba(255,255,255,.055); }
        .jp-schedule-list > div:last-child { border-bottom: 0; }
        .jp-schedule-list dt { color: var(--ia-muted-dim); font-size: .7rem; }
        .jp-schedule-list dd { color: var(--ia-cream); font-size: .74rem; font-weight: 650; text-align: right; white-space: nowrap; }
        .jp-region-list { list-style: none; padding: .45rem 1.1rem 0; }
        .jp-region-list li { display: flex; align-items: center; gap: .55rem; padding: .48rem 0; color: var(--ia-cream); font-size: .77rem; }
        .jp-region-list li > span { width: 6px; height: 6px; border-radius: 50%; background: var(--ia-copper); }
        .jp-region-note { padding: .45rem 1.1rem 1rem; color: var(--ia-muted-dim); font-size: .71rem; line-height: 1.5; }
        .jp-regulation { margin-top: 1.25rem; }
        .jp-regulation-heading { align-items: flex-end; }
        .jp-regulation-heading p { max-width: 68ch; margin-top: .35rem; color: var(--ia-muted-dim); font-size: .74rem; line-height: 1.5; }
        .jp-regulation-heading > small { flex: 0 0 auto; color: var(--ia-muted-dim); font-size: .68rem; }
        .jp-language-tabs { display: flex; gap: .15rem; padding: .7rem 1.1rem 0; border-bottom: 1px solid var(--ia-surface-border); }
        .jp-language-tabs button { padding: .55rem .75rem; border: 0; border-bottom: 2px solid transparent; background: none; color: var(--ia-muted-dim); font: inherit; font-size: .74rem; font-weight: 700; cursor: pointer; }
        .jp-language-tabs button:hover { color: var(--ia-muted); }
        .jp-language-tabs button.is-active { border-bottom-color: var(--ia-copper); color: var(--ia-copper-bright); }
        .jp-language-tabs button:focus-visible { outline: 2px solid var(--ia-copper); outline-offset: 2px; }
        .jp-regulation-document { max-width: 940px; padding: 1.25rem 1.35rem 1.5rem; }
        .jp-regulation-document > section + section { margin-top: 1.35rem; padding-top: 1.25rem; border-top: 1px solid var(--ia-surface-border); }
        .jp-regulation-document h3 { display: flex; align-items: baseline; gap: .65rem; color: var(--ia-cream); font-size: .9rem; font-weight: 750; }
        .jp-regulation-document h3 > span { color: var(--ia-copper); font-size: .66rem; letter-spacing: .06em; }
        .jp-regulation-document ol { list-style: none; margin-top: .65rem; }
        .jp-regulation-document li { display: grid; grid-template-columns: 2.5rem minmax(0, 1fr); gap: .6rem; padding: .38rem 0; }
        .jp-regulation-document li > span { color: var(--ia-muted-dim); font-size: .7rem; line-height: 1.65; }
        .jp-regulation-document li p { max-width: 75ch; color: var(--ia-muted); font-size: .78rem; line-height: 1.65; }
        .jp-regulation-empty { padding: 2rem 1.1rem; text-align: center; }
        .jp-regulation-empty strong { color: var(--ia-cream); font-size: .84rem; }
        .jp-regulation-empty p { max-width: 58ch; margin: .35rem auto 0; color: var(--ia-muted-dim); font-size: .75rem; line-height: 1.55; }
        @media (max-width: 900px) {
            .ip-mobile-nav { display: flex; gap: .25rem; overflow-x: auto; padding: .55rem 1rem; border-bottom: 1px solid var(--ia-surface-border); background: var(--ia-bg-soft); scrollbar-width: none; }
            .ip-mobile-nav::-webkit-scrollbar { display: none; }
            .ip-mobile-nav .ip-nav-item { flex: 0 0 auto; }
            .ip-content { padding: 1.25rem 1rem; }
        }
        @media (max-width: 700px) {
            .jp-page-heading { flex-direction: column; gap: .8rem; }
            .jp-task-item { grid-template-columns: 1fr; gap: 1rem; }
            .jp-task-dates { padding: .9rem 0 0; border-left: 0; border-top: 1px solid var(--ia-surface-border); }
            .jp-detail-facts dl { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .jp-detail-facts dl > div:nth-child(2) { border-right: 0; }
            .jp-detail-facts dl > div:nth-child(-n+2) { border-bottom: 1px solid var(--ia-surface-border); }
            .jp-detail-facts dl > div:nth-child(3) { padding-left: 0; }
            .jp-detail-grid { grid-template-columns: 1fr; }
            .jp-prose-list > div { grid-template-columns: 1fr; gap: .35rem; }
            .jp-regulation-heading { align-items: flex-start; flex-direction: column; }
        }
        @media (max-width: 480px) {
            .ip-header { padding-inline: 1rem; }
            .ip-header-title { font-size: 1.05rem; }
            .ip-user-btn-name { display: none; }
            .jp-task-heading { flex-direction: column; gap: .6rem; }
            .jp-task-summary [aria-hidden="true"] { display: none; }
            .jp-task-summary { flex-direction: column; align-items: flex-start; }
            .jp-task-empty { align-items: flex-start; justify-content: flex-start; min-height: 0; }
            .jp-detail-hero { flex-direction: column; gap: .7rem; }
            .jp-detail-facts dl { grid-template-columns: 1fr; }
            .jp-detail-facts dl > div { padding-inline: 0; border-right: 0; border-bottom: 1px solid var(--ia-surface-border); }
            .jp-detail-facts dl > div:last-child { border-bottom: 0; }
            .jp-regulation-document { padding-inline: 1rem; }
            .jp-regulation-document li { grid-template-columns: 2rem minmax(0, 1fr); gap: .35rem; }
        }
    </style>
</head>
<body class="ip-shell">
    <aside class="ip-sidebar">
        <a href="{{ route('juri.dashboard') }}" class="ip-brand">
            <span class="ip-brand-mark"><span></span></span>
            <span class="ip-brand-text">
                <span class="ip-brand-eyebrow">TFSF</span>
                <span class="ip-brand-title">{{ __('juri.login.heading') }}</span>
            </span>
        </a>

        <nav class="ip-nav">
            <a href="{{ route('juri.dashboard') }}" class="ip-nav-item {{ request()->routeIs('juri.dashboard') ? 'is-active' : '' }}">
                <x-juri.icon name="dashboard" />
                {{ __('juri.nav.dashboard') }}
            </a>
            <a href="{{ route('juri.assignments.index') }}" class="ip-nav-item {{ request()->routeIs('juri.assignments.*') ? 'is-active' : '' }}">
                <x-juri.icon name="assignments" />
                {{ __('juri.nav.assignments') }}
            </a>
            <div x-data="{ o: {{ request()->routeIs('juri.profile.*') || request()->routeIs('juri.password.edit') ? 'true' : 'false' }} }">
                <button type="button" class="ip-nav-group-btn {{ request()->routeIs('juri.profile.*') || request()->routeIs('juri.password.edit') ? 'is-active' : '' }}" @click="o = !o" :aria-expanded="o.toString()">
                    <x-juri.icon name="account" />
                    <span class="ip-nav-group-label">{{ __('juri.nav.profile') }}</span>
                    <x-juri.icon name="chevron-right" class="ip-nav-group-chevron" />
                </button>
                <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                    <a href="{{ route('juri.profile.edit') }}" class="ip-nav-item {{ request()->routeIs('juri.profile.*') ? 'is-active' : '' }}">
                        {{ __('juri.nav.profile') }}
                    </a>
                    <a href="{{ route('juri.password.edit') }}" class="ip-nav-item {{ request()->routeIs('juri.password.edit') ? 'is-active' : '' }}">
                        {{ __('juri.nav.password') }}
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
                <nav class="ip-lang" aria-label="{{ __('juri.eyebrow') }}">
                    @foreach (config('locales.supported') as $code => $label)
                        <a href="{{ route('juri.language', $code) }}" class="{{ app()->getLocale() === $code ? 'is-active' : '' }}">{{ strtoupper($code) }}</a>
                    @endforeach
                </nav>

                <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                    <button type="button" class="ip-user-btn" @click="open = !open" :aria-expanded="open.toString()">
                        <span class="ip-avatar">{{ $initials }}</span>
                        <span class="ip-user-btn-name">{{ $displayName }}</span>
                        <x-juri.icon name="chevron-down" />
                    </button>

                    <div class="ip-dropdown" x-show="open" x-cloak x-transition>
                        <div class="ip-dropdown-email">{{ $juror->email }}</div>

                        <a href="{{ route('juri.profile.edit') }}" class="ip-dropdown-item">
                            <x-juri.icon name="account" />
                            {{ __('juri.nav.profile') }}
                        </a>

                        <form method="POST" action="{{ route('juri.logout') }}">
                            @csrf
                            <button type="submit" class="ip-dropdown-item is-danger">
                                <x-juri.icon name="logout" />
                                {{ __('juri.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <nav class="ip-mobile-nav" aria-label="{{ __('juri.nav.mobile') }}">
            <a href="{{ route('juri.dashboard') }}" class="ip-nav-item {{ request()->routeIs('juri.dashboard') ? 'is-active' : '' }}">
                <x-juri.icon name="dashboard" />
                {{ __('juri.nav.dashboard') }}
            </a>
            <a href="{{ route('juri.assignments.index') }}" class="ip-nav-item {{ request()->routeIs('juri.assignments.*') ? 'is-active' : '' }}">
                <x-juri.icon name="assignments" />
                {{ __('juri.nav.assignments') }}
            </a>
            <a href="{{ route('juri.profile.edit') }}" class="ip-nav-item {{ request()->routeIs('juri.profile.*') ? 'is-active' : '' }}">
                <x-juri.icon name="account" />
                {{ __('juri.nav.profile') }}
            </a>
        </nav>

        <main class="ip-content">
            @if (session('status'))
                <div class="ip-status">{{ session('status') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
