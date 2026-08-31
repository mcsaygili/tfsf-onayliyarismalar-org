<?php

namespace App\Http\Controllers\Temsilci;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSubmissionApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $temsilci = Auth::guard('temsilci')->user();

        return view('temsilci.dashboard', [
            'temsilci' => $temsilci,
            'competitionCount' => $temsilci->competitions()->count(),
            'pendingApprovalCount' => CompetitionSubmissionApproval::query()
                ->where('status', 'pending')
                ->whereHas('submission.entry.competition', fn ($query) => $query->where('representative_id', $temsilci->id))
                ->count(),
            'upcomingDeadlines' => $temsilci->competitions()->with('translations')
                ->whereNotNull('evaluation_ends_at')->where('evaluation_ends_at', '>=', now())
                ->orderBy('evaluation_ends_at')->limit(5)->get(),
        ]);
    }
}
