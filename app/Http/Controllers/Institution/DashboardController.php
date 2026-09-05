<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        if (Auth::guard('institution')->user()->isSecretariat()) {
            return app(\App\Http\Controllers\SecretariatController::class)->dashboard(request(), app(\App\Services\InstitutionCompetitionAccess::class));
        }
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.dashboard', [
            'institution' => $institution,
            'hasCompleteProfile' => $institution->hasCompleteProfile(),
            'staffCount' => $institution->staff()->count(),
        ]);
    }
}
