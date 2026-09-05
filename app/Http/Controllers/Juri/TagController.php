<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\JuryTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function index(Request $request, Competition $competition, CompetitionCategory $category, JuryTagService $tags): JsonResponse
    {
        return response()->json(['tags' => $tags->listing($request->user('juri'), $competition, $category)]);
    }

    public function store(Request $request, Competition $competition, CompetitionCategory $category, JuryTagService $tags): JsonResponse
    {
        $tags->authorize($request->user('juri'), $competition, $category);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/']]);

        return response()->json(['tag' => $tags->create($request->user('juri'), $competition, $category, $data)]);
    }

    public function destroy(Request $request, Competition $competition, CompetitionCategory $category, string $tag, JuryTagService $tags): Response
    {
        $tags->delete($request->user('juri'), $competition, $category, $tag);

        return response()->noContent();
    }

    public function attach(Request $request, Competition $competition, CompetitionCategory $category, string $tag, string $photo, JuryTagService $tags): JsonResponse
    {
        return response()->json(['tag' => $tags->assign($request->user('juri'), $competition, $category, $tag, $photo, true)]);
    }

    public function detach(Request $request, Competition $competition, CompetitionCategory $category, string $tag, string $photo, JuryTagService $tags): JsonResponse
    {
        return response()->json(['tag' => $tags->assign($request->user('juri'), $competition, $category, $tag, $photo, false)]);
    }
}
