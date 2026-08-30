<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionType;
use App\Services\CompetitionPhaseService;
use App\Services\PublicCompetitionCatalogue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(Request $request, PublicCompetitionCatalogue $catalogue, CompetitionPhaseService $phases): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'audience' => in_array($request->query('audience'), array_column(CompetitionAudience::cases(), 'value'), true)
                ? $request->query('audience') : null,
            'type' => $request->query('type'),
            'phase' => in_array($request->query('phase'), ['open', 'upcoming', 'evaluation', 'completed'], true)
                ? $request->query('phase') : null,
            'year' => filter_var($request->query('year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2040]]) ?: null,
        ];

        $competitions = $catalogue->filteredQuery($filters)->paginate(12)->withQueryString();
        $competitions->getCollection()->each(
            fn ($competition) => $competition->setAttribute('operational_phase', $phases->phase($competition))
        );

        return view('public.competitions.index', [
            'competitions' => $competitions,
            'filters' => $filters,
            'competitionTypes' => CompetitionType::query()->active()->ordered()->with('translations')->get(),
            'years' => Competition::query()->publiclyVisible()
                ->whereNotNull('application_ends_at')
                ->orderByDesc('application_ends_at')
                ->pluck('application_ends_at')
                ->map(fn ($date) => Carbon::parse($date)->year)
                ->unique()->values(),
            'audienceCounts' => [
                'all' => Competition::query()->publiclyVisible()->count(),
                'national' => Competition::query()->publiclyVisible()->where('audience', CompetitionAudience::National)->count(),
                'international' => Competition::query()->publiclyVisible()->where('audience', CompetitionAudience::International)->count(),
            ],
        ]);
    }

    public function show(Competition $competition, CompetitionPhaseService $phases): View
    {
        abort_unless($competition->newQuery()->whereKey($competition->getKey())->publiclyVisible()->exists(), 404);

        $competition->load([
            'translations', 'institution', 'competitionType.translations',
            'participantApprovalProcess.translations',
            'captureRegions.country.translations', 'captureRegions.city.translations',
            'categories.translations', 'categories.genders.translations',
            'categories.ageEligibilityRule.translations', 'categories.memberGroups.translations',
            'categories.captureDevices.translations', 'categories.processingMethods.translations',
            'regulationSnapshots',
        ]);

        $locale = app()->getLocale();
        $regulationContent = $competition->regulationSnapshots->first()?->content ?? [];
        $regulation = $regulationContent[$locale]
            ?? $regulationContent[config('locales.default')]
            ?? collect($regulationContent)->first()
            ?? [];
        $phase = $phases->phase($competition);
        $entryUrl = $competition->infrastructure_provider === CompetitionInfrastructureProvider::External
            ? $competition->external_entry_url
            : route('competitions.show', $competition);

        return view('public.competitions.show', compact('competition', 'phase', 'regulation', 'entryUrl'));
    }
}
