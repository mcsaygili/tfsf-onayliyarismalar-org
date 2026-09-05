<?php

namespace App\Http\Controllers\Result;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionResultPublication;
use App\Services\CompetitionResultPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionResultController extends Controller
{
    public function index(Request $request, CompetitionResultPresentationService $presentation): View
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $search = trim($input['q'] ?? '');
        $publications = CompetitionResultPublication::query()->currentPublic()
            ->when($search !== '', fn ($query) => $query->where('search_text', 'like', '%'.$search.'%'))
            ->orderByDesc('published_at')->paginate(18)->withQueryString();

        return view('result.competitions.index', compact('publications', 'search', 'presentation'));
    }

    public function show(Competition $competition, CompetitionResultPresentationService $presentation): View
    {
        $publication = CompetitionResultPublication::query()->currentPublic()->where('competition_id', $competition->id)->firstOrFail();

        return view('result.competitions.show', $presentation->forPublication($publication) + [
            'preview' => false,
            'publicationHistory' => $competition->resultPublications()->where('published_at', '<=', now())->get(),
        ]);
    }
}
