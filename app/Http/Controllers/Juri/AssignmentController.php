<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\JuryTaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(JuryTaskService $tasks): View
    {
        $juror = Auth::guard('juri')->user();

        return view('juri.assignments.index', [
            'competitions' => $tasks->queryFor($juror)->paginate(10),
            'assignmentCount' => $juror->categoryAssignments()->count(),
            'nextMilestone' => $tasks->nextMilestoneFor($juror),
        ]);
    }

    public function show(Competition $competition, JuryTaskService $tasks): View
    {
        $juror = Auth::guard('juri')->user();
        $competition = $tasks->detailFor($juror, $competition);

        return view('juri.assignments.show', [
            'competition' => $competition,
            'regulationSnapshot' => $competition->regulationSnapshots->first(),
        ]);
    }
}
