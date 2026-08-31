<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EysUser;
use App\Services\CompetitionOperationsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CompetitionOperationsService $operations): View
    {
        return view('eys.dashboard', [
            'eysUser' => Auth::guard('eys')->user(),
            'userCount' => EysUser::count(),
            'metrics' => $operations->metrics(),
            'attentionQueue' => $operations->attentionQueue(),
        ]);
    }
}
