<?php

namespace App\Http\Controllers\Institution;

use App\Enums\CompetitionSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\InstitutionCompetitionOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompetitionOperationsController extends Controller
{
    public function index(Request $request, InstitutionCompetitionOperations $operations)
    {
        $filters = $request->validate(['state' => ['nullable', Rule::in(['ongoing', 'results', 'cancelled'])], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $competitions = $operations->competitions($request->user('institution'))->with(['translations', 'institution:id,name'])
            ->when(($filters['state'] ?? null) === 'ongoing', fn ($q) => $q->whereNull('results_published_at')->where('publication_state', '!=', 'cancelled'))
            ->when(($filters['state'] ?? null) === 'results', fn ($q) => $q->whereNotNull('results_published_at'))
            ->when(($filters['state'] ?? null) === 'cancelled', fn ($q) => $q->where('publication_state', 'cancelled'))
            ->orderByDesc('created_at')->orderBy('id')->paginate(20)->withQueryString();

        return view('institution.operations.index', compact('competitions', 'filters'));
    }

    public function show(Request $request, Competition $competition, InstitutionCompetitionOperations $operations)
    {
        return DB::transaction(function () use ($request, $competition, $operations) {
            $competition = $operations->competitions($request->user('institution'))->whereKey($competition->id)->firstOrFail();
            $competition->load(['translations', 'institution:id,name', 'representative:id,first_name,last_name', 'categories.translations']);
            $filters = $request->validate([
                'category' => ['nullable', 'uuid', Rule::exists('competition_categories', 'id')->where('competition_id', $competition->id)],
                'status' => ['nullable', Rule::enum(CompetitionSubmissionStatus::class)],
                'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            ]);
            $statistics = $operations->statistics($competition, $filters);
            $participants = $operations->participants($competition, $filters)->paginate(25)->withQueryString();

            return view('institution.operations.show', compact('competition', 'filters', 'statistics', 'participants'));
        });
    }
}
