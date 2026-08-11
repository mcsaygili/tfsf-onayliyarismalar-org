<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Models\PortfolioSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('uye.dashboard', [
            'photoCount' => $user->photos()->count(),
            'maxPhotos' => PortfolioSetting::current()->max_photos_per_user,
            'equipmentCount' => $user->equipment()->count(),
            'lastLoginAt' => $user->last_login_at,
            'memberSince' => $user->created_at,
        ]);
    }
}
