<?php

namespace App\Http\Controllers\Eys;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\EysUser;
use App\Services\CompetitionOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CompetitionOperationsService $operations): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.collect(CompetitionStatus::cases())->pluck('value')->join(',')],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'overdue' => ['nullable', 'boolean'],
        ]);
        $filters = [
            'status' => $validated['status'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'overdue' => $request->boolean('overdue'),
        ];

        return view('eys.dashboard', [
            'eysUser' => Auth::guard('eys')->user(),
            'userCount' => EysUser::count(),
            'metrics' => $operations->metrics(),
            'attentionQueue' => $operations->attentionQueue($filters),
            'operationFilters' => $filters,
            'competitionStatuses' => CompetitionStatus::cases(),
        ]);
    }
}
