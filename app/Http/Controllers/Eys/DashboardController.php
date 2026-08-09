<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EysUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('eys.dashboard', [
            'eysUser' => Auth::guard('eys')->user(),
            'userCount' => EysUser::count(),
        ]);
    }
}
