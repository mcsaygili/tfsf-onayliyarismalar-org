<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.dashboard', [
            'institution' => $institution,
            'staffCount' => $institution->staff()->count(),
        ]);
    }
}
