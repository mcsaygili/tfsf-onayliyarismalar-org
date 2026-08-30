<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Services\JuryTaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(JuryTaskService $tasks): View
    {
        $juror = Auth::guard('juri')->user();
        $taskQuery = $tasks->queryFor($juror);

        return view('juri.dashboard', [
            'juri' => $juror,
            'competitions' => (clone $taskQuery)->limit(3)->get(),
            'competitionCount' => (clone $taskQuery)->count(),
            'assignmentCount' => $juror->categoryAssignments()->count(),
            'nextMilestone' => $tasks->nextMilestoneFor($juror),
        ]);
    }
}
