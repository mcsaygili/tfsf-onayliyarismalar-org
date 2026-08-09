<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use Illuminate\View\View;

/**
 * EYS'in Kurum/Temsilci/Jüri/Üye modülleri için geçici gösterge panelleri —
 * her biri şimdilik sadece o modülün toplam kayıt sayısını gösterir (bkz.
 * proje planı: bu modüllerin gerçek CRUD ekranları ayrı bir iş).
 */
class ModuleDashboardController extends Controller
{
    public function institution(): View
    {
        return view('eys.modules.dashboard', [
            'moduleTitle' => __('eys.module_names.Institution'),
            'totalLabel' => __('eys.modules.total_institutions'),
            'totalCount' => Institution::count(),
            'icon' => 'institution',
        ]);
    }

    public function temsilci(): View
    {
        return view('eys.modules.dashboard', [
            'moduleTitle' => __('eys.module_names.Temsilci'),
            'totalLabel' => __('eys.modules.total_representatives'),
            'totalCount' => Temsilci::count(),
            'icon' => 'temsilci',
        ]);
    }

    public function juri(): View
    {
        return view('eys.modules.dashboard', [
            'moduleTitle' => __('eys.module_names.Juri'),
            'totalLabel' => __('eys.modules.total_jurors'),
            'totalCount' => Juri::count(),
            'icon' => 'juri',
        ]);
    }

    public function uye(): View
    {
        return view('eys.modules.dashboard', [
            'moduleTitle' => __('eys.module_names.Uye'),
            'totalLabel' => __('eys.modules.total_members'),
            'totalCount' => User::count(),
            'icon' => 'uye',
        ]);
    }
}
