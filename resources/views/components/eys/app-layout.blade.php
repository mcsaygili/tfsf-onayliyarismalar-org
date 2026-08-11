@props(['title' => null])
@php
    $eysUser = auth('eys')->user();
    $displayName = trim(($eysUser->first_name ?? '').' '.($eysUser->last_name ?? '')) ?: $eysUser->email;
    $initials = $eysUser->first_name
        ? strtoupper(mb_substr($eysUser->first_name, 0, 1).mb_substr($eysUser->last_name ?? '', 0, 1))
        : strtoupper(mb_substr($eysUser->email, 0, 1));

    // Sidebar'daki MODÜLLER dropdown'ları her zaman "Eys" team context'inde
    // render edilir (bkz. routes/eys.php'nin dış 'team:Eys' middleware'i),
    // bu yüzden her modülün kendi izinleri o modülün TEAM'ine geçici olarak
    // geçilerek kontrol edilir, sonrasında context geri alınır. Route::has()
    // kontrolleri, bu modüllerin CRUD ekranları aşamalı olarak eklenirken
    // (bkz. proje geçmişi) henüz kurulmamış bir route'a link vermemek için.
    $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
    $moduleLinks = [];
    foreach (\App\Enums\Module::cases() as $m) {
        if ($m === \App\Enums\Module::Eys) {
            continue;
        }
        $permissionRegistrar->setPermissionsTeamId($m->value);
        // Spatie'nin roles/permissions ilişkileri model üzerinde cache'lendiği
        // için team değişse bile eski team'in rolleri döner — her switch'te
        // ilişkiyi düşürüp taze sorgu yapılmasını sağlıyoruz.
        $eysUser->unsetRelation('roles')->unsetRelation('permissions');

        $links = [];
        if (\Illuminate\Support\Facades\Route::has('eys.'.strtolower($m->name).'.dashboard') && $eysUser->can($m->key().'.dashboard.view')) {
            $links[] = [
                'route' => 'eys.'.strtolower($m->name).'.dashboard',
                'label' => __('eys.nav.module_dashboard'),
                'icon' => 'dashboard',
                'activePatterns' => ['eys.'.strtolower($m->name).'.dashboard'],
            ];
        }
        if ($m === \App\Enums\Module::Institution && \Illuminate\Support\Facades\Route::has('eys.institutions.index') && $eysUser->can('institution.institutions.manage')) {
            $links[] = [
                'route' => 'eys.institutions.index',
                'label' => __('eys.institution.title'),
                'icon' => 'institution',
                'activePatterns' => ['eys.institutions.*', 'eys.institution-staff.*'],
            ];
        }
        if ($m === \App\Enums\Module::Institution && \Illuminate\Support\Facades\Route::has('eys.competitions.index') && $eysUser->can('institution.competitions.manage')) {
            $links[] = [
                'route' => 'eys.competitions.index',
                'label' => __('eys.competitions.title'),
                'icon' => 'competitions',
                'activePatterns' => ['eys.competitions.*'],
            ];
        }
        if ($m === \App\Enums\Module::Temsilci && \Illuminate\Support\Facades\Route::has('eys.temsilciler.index') && $eysUser->can('representative.representatives.manage')) {
            $links[] = [
                'route' => 'eys.temsilciler.index',
                'label' => __('eys.temsilci.title'),
                'icon' => 'temsilci',
                'activePatterns' => ['eys.temsilciler.*'],
            ];
        }
        if ($m === \App\Enums\Module::Juri && \Illuminate\Support\Facades\Route::has('eys.juriler.index') && $eysUser->can('jury.jurors.manage')) {
            $links[] = [
                'route' => 'eys.juriler.index',
                'label' => __('eys.juri.title'),
                'icon' => 'juri',
                'activePatterns' => ['eys.juriler.*'],
            ];
        }
        if ($m === \App\Enums\Module::Uye && \Illuminate\Support\Facades\Route::has('eys.uyeler.index') && $eysUser->can('member.members.manage')) {
            $links[] = [
                'route' => 'eys.uyeler.index',
                'label' => __('eys.uye.title'),
                'icon' => 'uye',
                'activePatterns' => ['eys.uyeler.*'],
            ];
        }
        $moduleLinks[$m->name] = $links;
    }
    // Bu layout'taki geri kalan TÜM @can() kontrolleri (YÖNETİM bölümü)
    // 'eys.*' izinlerini kontrol ediyor ve Eys team'i varsayıyor — bir modül
    // gösterge paneli sayfasında (team:Institution vb. middleware'i team'i
    // değiştirmiş olabilir) bile bu varsayım korunmalı, o yüzden team'i
    // burada kalıcı olarak Eys'e sabitliyoruz.
    $permissionRegistrar->setPermissionsTeamId(\App\Enums\Module::Eys->value);
    $eysUser->unsetRelation('roles')->unsetRelation('permissions');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="ip-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? __('eys.eyebrow') }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        /**
         * Uygulama genelinde tek tip onay modalı + toast bildirim mimarisi.
         * window.eysConfirm(message, target) — target bir <form> ise onaylanınca
         * gönderilir, fonksiyon ise onaylanınca çağrılır (bkz. Dosya Yöneticisi).
         * window.eysToast(type, message) — type: success|error|info.
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
                    window.eysToast('info', @js(__('eys.common.action_cancelled')));
                },
            });
        });

        window.eysToast = (type, message) => Alpine.store('toast').push(type, message);

        window.eysConfirm = (message, target) => {
            Alpine.store('confirmModal').show(message, () => {
                if (target instanceof HTMLFormElement) {
                    target.requestSubmit();
                } else if (typeof target === 'function') {
                    return target();
                }
            });
        };

        @if (session('status'))
            document.addEventListener('DOMContentLoaded', () => window.eysToast('success', @js(session('status'))));
        @endif
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

        /* ---- Menü bölüm başlıkları (GENEL/MODÜLLER/YÖNETİM) ---- */
        .ip-nav-section {
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ia-muted-dim);
            padding: 0 .7rem;
            margin: 1.15rem 0 .4rem;
        }
        .ip-nav-section:first-child { margin-top: 0; }

        /* ---- Modüller — aç/kapa grup başlığı ---- */
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
        .ip-nav-empty { padding: .45rem .6rem; font-size: .78rem; color: var(--ia-muted-dim); font-style: italic; }

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

        /* Liste sayfalarındaki 3 panel (başlık, filtre, tablo) arası boşluk. */
        .ip-panel-stack { display: flex; flex-direction: column; gap: 1.5rem; }

        /* ---- Onay modalı — silme/güncelleme gibi geri alınamaz işlemler
           öncesi tek tip onay penceresi. Sadece "Onay"/"İptal" ile
           kapanır; arka plana tıklama veya Escape ile kapanmaz. ---- */
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
            color: var(--ia-cream);
            font-size: .92rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        .ip-modal-actions { display: flex; justify-content: flex-end; gap: .75rem; }

        /* ---- Toast bildirimleri — tüm kayıt/güncelleme/silme akışlarının
           ortak geri bildirim mimarisi. Ekranın altında, ortada birikir. ---- */
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

        /* ---- Form bileşenleri (x-eys.input/label/button/input-error) ---- */
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
        /* ---- Tek tip buton stili — tüm eys/* butonları "Geri Dön" tasarımını
           kullanır: şeffaf zemin, ince kenarlık, kompakt boyut. ---- */
        .ia-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: transparent;
            color: var(--ia-muted);
            font-family: 'Figtree', sans-serif;
            font-weight: 700;
            font-size: .82rem;
            padding: .55rem 1.1rem;
            border-radius: 7px;
            border: 1px solid var(--ia-surface-border);
            cursor: pointer;
            text-decoration: none;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease, transform .1s ease;
        }
        .ia-btn:hover { background: rgba(201,168,76,.06); color: var(--ia-cream); border-color: var(--ia-copper); }
        .ia-btn:active { transform: translateY(1px); }
        .ia-btn svg { width: 14px; height: 14px; }

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

        /* ---- Liste sayfası mimarisi: 1) breadcrumb/action, 2) filtre,
           3) listeleme — tüm eys/*/index.blade.php'lerde standart. ---- */
        .ip-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .78rem; color: var(--ia-muted-dim); }
        .ip-breadcrumb-item { color: var(--ia-muted-dim); text-decoration: none; }
        .ip-breadcrumb-item:hover { color: var(--ia-copper-bright); }
        .ip-breadcrumb-item.is-current { color: var(--ia-muted); }
        .ip-breadcrumb-sep { opacity: .5; }

        .ip-filter-toggle-label { display: flex; align-items: center; gap: .55rem; font-weight: 700; font-size: .9rem; color: var(--ia-cream); margin-bottom: 1.1rem; }
        .ip-filter-toggle-label svg { width: 16px; height: 16px; color: var(--ia-copper); }
        .ip-filter-total { font-weight: 400; font-size: .78rem; color: var(--ia-muted-dim); }
        .ip-filter-grid { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
        .ip-filter-field { display: flex; flex-direction: column; gap: .4rem; min-width: 180px; }
        .ip-filter-field label { font-size: .74rem; font-weight: 600; color: var(--ia-muted); }
        .ip-filter-actions { display: flex; gap: .6rem; }
        .ip-filter-search { position: relative; }
        .ip-filter-search svg {
            position: absolute;
            left: .8rem;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: var(--ia-muted-dim);
            pointer-events: none;
        }
        .ip-filter-search .ia-input { padding-left: 2.4rem; }

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
        .ip-table td.ip-cell-name { color: var(--ia-cream); }
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
        .ip-badge.is-draft { color: #9aa0ac; background: rgba(154,160,172,.12); }
        .ip-badge.is-pending { color: #e0b25a; background: rgba(224,178,90,.14); }
        .ip-badge.is-needs-info { color: #6fb3d9; background: rgba(111,179,217,.14); }
        .ip-badge.is-approved { color: #8fcf93; background: rgba(88,140,92,.14); }
        .ip-badge.is-rejected { color: #e0857a; background: rgba(224,133,122,.12); }

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

        /* ---- Dosya Yöneticisi ---- */
        .fm { position: relative; }
        .fm-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .9rem; }
        .fm-toolbar .fm-spacer { flex: 1; }
        .fm-toolbar input[type="search"] { width: 200px; }
        .fm-selectbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .9rem; padding: .55rem .8rem; border-radius: 8px; background: rgba(201,168,76,.1); border: 1px solid var(--ia-surface-border); font-size: .85rem; color: var(--ia-cream); }
        .fm-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; font-size: .82rem; color: var(--ia-muted-dim); margin-bottom: .9rem; }
        .fm-crumbs button { color: var(--ia-muted-dim); background: none; border: none; cursor: pointer; padding: 0; font: inherit; }
        .fm-crumbs button:hover { color: var(--ia-copper-bright); }
        .fm-progress-track { height: 5px; border-radius: 4px; background: var(--ia-bg); overflow: hidden; }
        .fm-progress-fill { height: 100%; background: var(--ia-copper); transition: width .2s; }
        .fm-empty { padding: 3rem 0; text-align: center; color: var(--ia-muted-dim); font-size: .85rem; }
        .fm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
        .fm-folder-tile { display: flex; align-items: center; gap: .4rem; background: var(--ia-surface); border: 1px solid var(--ia-surface-border); border-radius: 10px; padding: 0 .6rem; height: 46px; }
        .fm-folder-tile.is-selected { border-color: var(--ia-copper-bright); }
        .fm-folder-tile button.fm-open { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 0; text-align: left; background: none; border: none; cursor: pointer; color: inherit; font: inherit; }
        .fm-folder-tile .fm-name { font-size: .82rem; font-weight: 600; color: var(--ia-cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fm-file-tile { background: var(--ia-surface); border: 1px solid var(--ia-surface-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
        .fm-file-tile.is-selected { border-color: var(--ia-copper-bright); }
        .fm-file-thumb { position: relative; aspect-ratio: 4/3; background: var(--ia-bg); display: grid; place-items: center; overflow: hidden; }
        .fm-file-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .fm-file-thumb .fm-ext { font-size: .7rem; text-transform: uppercase; font-weight: 700; color: var(--ia-muted-dim); }
        .fm-file-thumb input[type="checkbox"] { position: absolute; top: .4rem; left: .4rem; z-index: 1; }
        .fm-file-body { padding: .5rem; display: flex; align-items: center; gap: .25rem; }
        .fm-file-body .fm-meta { min-width: 0; flex: 1; }
        .fm-file-body .fm-name { font-size: .74rem; font-weight: 600; color: var(--ia-cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .fm-file-body .fm-size { font-size: .68rem; color: var(--ia-muted-dim); }
        .fm-icon-btn { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 6px; background: none; border: none; color: var(--ia-muted-dim); cursor: pointer; flex-shrink: 0; }
        .fm-icon-btn:hover { background: var(--ia-bg); color: var(--ia-cream); }
        .fm-icon-btn.is-danger { color: #d98a7a; }
        .fm-icon-btn.is-danger:hover { background: rgba(217,138,122,.12); }
        .fm-list { background: var(--ia-surface); border: 1px solid var(--ia-surface-border); border-radius: 10px; overflow: hidden; }
        .fm-list-row { display: flex; align-items: center; gap: .7rem; padding: .55rem .9rem; border-bottom: 1px solid var(--ia-surface-border); }
        .fm-list-row:last-child { border-bottom: none; }
        .fm-list-row .fm-thumb-sm { width: 30px; height: 30px; border-radius: 6px; object-fit: cover; flex-shrink: 0; }
        .fm-list-row .fm-ext-sm { width: 30px; height: 30px; border-radius: 6px; background: var(--ia-bg); display: grid; place-items: center; font-size: .6rem; font-weight: 700; text-transform: uppercase; color: var(--ia-muted-dim); flex-shrink: 0; }
        .fm-list-row .fm-name { flex: 1; min-width: 0; font-size: .84rem; color: var(--ia-cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fm-pagination { display: flex; align-items: center; justify-content: center; gap: .75rem; margin-top: 1.25rem; font-size: .84rem; color: var(--ia-muted-dim); }
        .fm-dropzone { position: absolute; inset: 0; z-index: 20; border-radius: 12px; border: 2px dashed var(--ia-copper-bright); background: rgba(201,168,76,.12); display: grid; place-items: center; pointer-events: none; }
        .fm-dropzone span { color: var(--ia-copper-bright); font-weight: 700; }
        .fm-overlay { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .fm-overlay-bg { position: absolute; inset: 0; background: rgba(5,7,12,.6); }
        .fm-dialog { position: relative; width: 100%; max-width: 380px; background: var(--ia-surface); border: 1px solid var(--ia-surface-border); border-radius: 12px; padding: 1.25rem; box-shadow: 0 20px 50px rgba(0,0,0,.4); }
        .fm-dialog h3 { font-size: .95rem; font-weight: 700; color: var(--ia-cream); margin-bottom: .8rem; }
        .fm-dialog-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
        .fm-lightbox { position: fixed; inset: 0; z-index: 210; background: rgba(0,0,0,.92); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; }
        .fm-lightbox img { max-height: 82vh; max-width: 100%; border-radius: 8px; }
        .fm-lightbox p { margin-top: .75rem; color: rgba(255,255,255,.8); font-size: .85rem; }
        .fm-lightbox-close { position: absolute; top: 1rem; right: 1.25rem; color: rgba(255,255,255,.7); font-size: 1.8rem; line-height: 1; background: none; border: none; cursor: pointer; }
        .fm-context-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    </style>
</head>
<body class="ip-shell">
    <aside class="ip-sidebar">
        <a href="{{ route('eys.dashboard') }}" class="ip-brand">
            <span class="ip-brand-mark"><span></span></span>
            <span class="ip-brand-text">
                <span class="ip-brand-eyebrow">TFSF</span>
                <span class="ip-brand-title">{{ __('eys.login.heading') }}</span>
            </span>
        </a>

        <nav class="ip-nav">
            <div class="ip-nav-section">{{ __('eys.nav.section_general') }}</div>
            <a href="{{ route('eys.dashboard') }}" class="ip-nav-item {{ request()->routeIs('eys.dashboard') ? 'is-active' : '' }}">
                <x-eys.icon name="dashboard" />
                {{ __('eys.nav.dashboard') }}
            </a>

            <div class="ip-nav-section">{{ __('eys.nav.section_modules') }}</div>
            @php
                $moduleIcons = ['Institution' => 'institution', 'Temsilci' => 'temsilci', 'Juri' => 'juri', 'Uye' => 'uye'];
            @endphp
            @foreach (\App\Enums\Module::cases() as $module)
                @continue($module === \App\Enums\Module::Eys)
                @php
                    $links = $moduleLinks[$module->name] ?? [];
                    $isModuleOpen = collect($links)->contains(fn ($l) => request()->routeIs(...$l['activePatterns']));
                @endphp
                <div x-data="{ o: {{ $isModuleOpen ? 'true' : 'false' }} }">
                    <button type="button" class="ip-nav-group-btn" @click="o = !o" :aria-expanded="o.toString()">
                        <x-eys.icon :name="$moduleIcons[$module->name] ?? 'dashboard'" />
                        <span class="ip-nav-group-label">{{ $module->label() }}</span>
                        <x-eys.icon name="chevron-right" class="ip-nav-group-chevron" />
                    </button>
                    <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                        @forelse ($links as $link)
                            <a href="{{ route($link['route']) }}" class="ip-nav-item {{ request()->routeIs(...$link['activePatterns']) ? 'is-active' : '' }}">
                                <x-eys.icon :name="$link['icon']" />
                                {{ $link['label'] }}
                            </a>
                        @empty
                            <div class="ip-nav-empty">{{ __('eys.nav.soon') }}</div>
                        @endforelse
                    </div>
                </div>
            @endforeach

            <div class="ip-nav-section">{{ __('eys.nav.section_management') }}</div>
            <a href="{{ route('eys.users.index') }}" class="ip-nav-item {{ request()->routeIs('eys.users.*') ? 'is-active' : '' }}">
                <x-eys.icon name="staff" />
                {{ __('eys.nav.users') }}
            </a>
            @can('eys.roles.manage')
                <a href="{{ route('eys.roles.index') }}" class="ip-nav-item {{ request()->routeIs('eys.roles.*') ? 'is-active' : '' }}">
                    <x-eys.icon name="role" />
                    {{ __('eys.role.title') }}
                </a>
            @endcan
            @can('eys.permissions.manage')
                <a href="{{ route('eys.permissions.index') }}" class="ip-nav-item {{ request()->routeIs('eys.permissions.*') ? 'is-active' : '' }}">
                    <x-eys.icon name="permission" />
                    {{ __('eys.permission.title') }}
                </a>
            @endcan
            @canany(['eys.countries.manage', 'eys.cities.manage', 'eys.education_levels.manage', 'eys.institution_types.manage', 'eys.photo_categories.manage', 'eys.photo_techniques.manage'])
                <div x-data="{ o: {{ request()->routeIs('eys.countries.*') || request()->routeIs('eys.cities.*') || request()->routeIs('eys.education-levels.*') || request()->routeIs('eys.institution-types.*') || request()->routeIs('eys.photo-categories.*') || request()->routeIs('eys.photo-techniques.*') ? 'true' : 'false' }} }">
                    <button type="button" class="ip-nav-group-btn" @click="o = !o" :aria-expanded="o.toString()">
                        <x-eys.icon name="layers" />
                        <span class="ip-nav-group-label">{{ __('eys.nav.reference_data') }}</span>
                        <x-eys.icon name="chevron-right" class="ip-nav-group-chevron" />
                    </button>
                    <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                        @can('eys.countries.manage')
                            <a href="{{ route('eys.countries.index') }}" class="ip-nav-item {{ request()->routeIs('eys.countries.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="country" />
                                {{ __('eys.nav.countries') }}
                            </a>
                        @endcan
                        @can('eys.cities.manage')
                            <a href="{{ route('eys.cities.index') }}" class="ip-nav-item {{ request()->routeIs('eys.cities.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="city" />
                                {{ __('eys.nav.cities') }}
                            </a>
                        @endcan
                        @can('eys.education_levels.manage')
                            <a href="{{ route('eys.education-levels.index') }}" class="ip-nav-item {{ request()->routeIs('eys.education-levels.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="education" />
                                {{ __('eys.nav.education_levels') }}
                            </a>
                        @endcan
                        @can('eys.institution_types.manage')
                            <a href="{{ route('eys.institution-types.index') }}" class="ip-nav-item {{ request()->routeIs('eys.institution-types.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="institution" />
                                {{ __('eys.nav.institution_types') }}
                            </a>
                        @endcan
                        @can('eys.photo_categories.manage')
                            <a href="{{ route('eys.photo-categories.index') }}" class="ip-nav-item {{ request()->routeIs('eys.photo-categories.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="camera" />
                                {{ __('eys.nav.photo_categories') }}
                            </a>
                        @endcan
                        @can('eys.photo_techniques.manage')
                            <a href="{{ route('eys.photo-techniques.index') }}" class="ip-nav-item {{ request()->routeIs('eys.photo-techniques.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="wand" />
                                {{ __('eys.nav.photo_techniques') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany
            @canany(['eys.equipment_types.manage', 'eys.equipment_brands.manage', 'eys.equipment_models.manage'])
                <div x-data="{ o: {{ request()->routeIs('eys.equipment-types.*') || request()->routeIs('eys.equipment-brands.*') || request()->routeIs('eys.equipment-models.*') ? 'true' : 'false' }} }">
                    <button type="button" class="ip-nav-group-btn" @click="o = !o" :aria-expanded="o.toString()">
                        <x-eys.icon name="equipment" />
                        <span class="ip-nav-group-label">{{ __('eys.nav.equipment_catalog') }}</span>
                        <x-eys.icon name="chevron-right" class="ip-nav-group-chevron" />
                    </button>
                    <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                        @can('eys.equipment_types.manage')
                            <a href="{{ route('eys.equipment-types.index') }}" class="ip-nav-item {{ request()->routeIs('eys.equipment-types.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="layers" />
                                {{ __('eys.nav.equipment_types') }}
                            </a>
                        @endcan
                        @can('eys.equipment_brands.manage')
                            <a href="{{ route('eys.equipment-brands.index') }}" class="ip-nav-item {{ request()->routeIs('eys.equipment-brands.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="tag" />
                                {{ __('eys.nav.equipment_brands') }}
                            </a>
                        @endcan
                        @can('eys.equipment_models.manage')
                            <a href="{{ route('eys.equipment-models.index') }}" class="ip-nav-item {{ request()->routeIs('eys.equipment-models.*') ? 'is-active' : '' }}">
                                <x-eys.icon name="grid" />
                                {{ __('eys.nav.equipment_models') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany
            @can('eys.mail_client.view')
                <a href="{{ route('eys.mail-client.dashboard') }}" class="ip-nav-item {{ request()->routeIs('eys.mail-client.*') ? 'is-active' : '' }}">
                    <x-eys.icon name="mail" />
                    {{ __('eys.nav.mail_client') }}
                </a>
            @endcan
            @can('eys.file_manager.view')
                <a href="{{ route('eys.filemanager.index') }}" class="ip-nav-item {{ request()->routeIs('eys.filemanager.*') ? 'is-active' : '' }}">
                    <x-eys.icon name="file-manager" />
                    {{ __('eys.nav.file_manager') }}
                </a>
            @endcan
            @canany(['eys.system_settings.manage'])
                <div x-data="{ o: {{ request()->routeIs('eys.system-settings.*') ? 'true' : 'false' }} }">
                    <button type="button" class="ip-nav-group-btn" @click="o = !o" :aria-expanded="o.toString()">
                        <x-eys.icon name="settings" />
                        <span class="ip-nav-group-label">{{ __('eys.nav.system_settings') }}</span>
                        <x-eys.icon name="chevron-right" class="ip-nav-group-chevron" />
                    </button>
                    <div class="ip-nav-group-body" x-show="o" x-cloak x-transition>
                        @can('eys.system_settings.manage')
                            <a href="{{ route('eys.system-settings.portfolio') }}" class="ip-nav-item {{ request()->routeIs('eys.system-settings.portfolio*') ? 'is-active' : '' }}">
                                <x-eys.icon name="camera" />
                                {{ __('eys.nav.portfolio_settings') }}
                            </a>
                            <a href="{{ route('eys.system-settings.maintenance') }}" class="ip-nav-item {{ request()->routeIs('eys.system-settings.maintenance*') ? 'is-active' : '' }}">
                                <x-eys.icon name="wrench" />
                                {{ __('eys.nav.maintenance_mode') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            @canany(['eys.regulation_sections.manage', 'eys.regulation_items.manage'])
                <div class="ip-nav-section">{{ __('eys.nav.section_competition_system') }}</div>
                @can('eys.regulation_sections.manage')
                    <a href="{{ route('eys.regulation-sections.index') }}" class="ip-nav-item {{ request()->routeIs('eys.regulation-sections.*') ? 'is-active' : '' }}">
                        <x-eys.icon name="document" />
                        {{ __('eys.nav.regulation_sections') }}
                    </a>
                @endcan
                @can('eys.regulation_items.manage')
                    <a href="{{ route('eys.regulation-items.index') }}" class="ip-nav-item {{ request()->routeIs('eys.regulation-items.*') ? 'is-active' : '' }}">
                        <x-eys.icon name="list-check" />
                        {{ __('eys.nav.regulation_items') }}
                    </a>
                @endcan
            @endcanany
        </nav>

        <div class="ip-sidebar-foot">TFSF · v{{ config('app.version', '0.1') }}</div>
    </aside>

    <div class="ip-main">
        <header class="ip-header">
            <div class="ip-header-title">{{ $title ?? '' }}</div>

            <div class="ip-header-right">
                <nav class="ip-lang" aria-label="{{ __('eys.eyebrow') }}">
                    @foreach (config('locales.supported') as $code => $label)
                        <a href="{{ route('eys.language', $code) }}" class="{{ app()->getLocale() === $code ? 'is-active' : '' }}">{{ strtoupper($code) }}</a>
                    @endforeach
                </nav>

                <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                    <button type="button" class="ip-user-btn" @click="open = !open" :aria-expanded="open.toString()">
                        <span class="ip-avatar">{{ $initials }}</span>
                        <span class="ip-user-btn-name">{{ $displayName }}</span>
                        <x-eys.icon name="chevron-down" />
                    </button>

                    <div class="ip-dropdown" x-show="open" x-cloak x-transition>
                        <div class="ip-dropdown-email">{{ $eysUser->email }}</div>

                        <a href="{{ route('eys.users.edit', $eysUser) }}" class="ip-dropdown-item">
                            <x-eys.icon name="account" />
                            {{ __('eys.nav.account') }}
                        </a>

                        <form method="POST" action="{{ route('eys.logout') }}">
                            @csrf
                            <button type="submit" class="ip-dropdown-item is-danger">
                                <x-eys.icon name="logout" />
                                {{ __('eys.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="ip-content">
            {{ $slot }}
        </main>
    </div>

    <div x-data class="ip-modal-overlay" x-show="$store.confirmModal.open" x-cloak x-transition.opacity>
        <div class="ip-modal" role="alertdialog" aria-modal="true">
            <p class="ip-modal-message" x-text="$store.confirmModal.message"></p>
            <div class="ip-modal-actions">
                <button type="button" class="ia-btn" @click="$store.confirmModal.cancel()">{{ __('eys.common.cancel') }}</button>
                <button type="button" class="ia-btn" @click="$store.confirmModal.confirm()">{{ __('eys.common.confirm') }}</button>
            </div>
        </div>
    </div>

    <div x-data class="ip-toast-stack">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="ip-toast" :class="'is-' + t.type" x-text="t.message" x-transition></div>
        </template>
    </div>

    @stack('scripts')
</body>
</html>
