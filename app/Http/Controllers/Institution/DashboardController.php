<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SecretariatController;
use App\Services\InstitutionCompetitionAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        if (Auth::guard('institution')->user()->isSecretariat()) {
            return app(SecretariatController::class)->dashboard(request(), app(InstitutionCompetitionAccess::class));
        }
        $institution = Auth::guard('institution')->user()->institution;

        return view('institution.dashboard', [
            'institution' => $institution,
            'hasCompleteProfile' => $institution->hasCompleteProfile(),
            'staffCount' => $institution->staff()->count(),
        ]);
    }
}
