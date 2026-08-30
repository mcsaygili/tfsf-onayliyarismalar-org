<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\CompetitionAudience;
use App\Http\Controllers\Controller;
use App\Services\CompetitionPhaseService;
use App\Services\PublicCompetitionCatalogue;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(PublicCompetitionCatalogue $catalogue, CompetitionPhaseService $phases): View
    {
        $base = $catalogue->baseQuery();
        $now = now();

        $openCompetitions = $catalogue->ordered(
            (clone $base)
                ->whereNull('results_published_at')
                ->where('application_starts_at', '<=', $now)
                ->where('application_ends_at', '>=', $now),
            $now,
        )->limit(4)->get();
        $nationalCompetitions = $catalogue->ordered(
            (clone $base)->where('audience', CompetitionAudience::National),
            $now,
        )->limit(4)->get();
        $internationalCompetitions = $catalogue->ordered(
            (clone $base)->where('audience', CompetitionAudience::International),
            $now,
        )->limit(4)->get();

        foreach ([$openCompetitions, $nationalCompetitions, $internationalCompetitions] as $competitions) {
            $competitions->each(fn ($competition) => $competition->setAttribute('operational_phase', $phases->phase($competition)));
        }

        return view('public.home', [
            'openCompetitions' => $openCompetitions,
            'nationalCompetitions' => $nationalCompetitions,
            'internationalCompetitions' => $internationalCompetitions,
            'counts' => [
                'all' => (clone $base)->count(),
                'national' => (clone $base)->where('audience', CompetitionAudience::National)->count(),
                'international' => (clone $base)->where('audience', CompetitionAudience::International)->count(),
                'open' => (clone $base)
                    ->whereNull('results_published_at')
                    ->where('application_starts_at', '<=', $now)
                    ->where('application_ends_at', '>=', $now)
                    ->count(),
            ],
        ]);
    }
}
