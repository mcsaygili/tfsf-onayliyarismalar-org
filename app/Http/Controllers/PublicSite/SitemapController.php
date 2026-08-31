<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('public.home'), 'lastmod' => now()],
            ['loc' => route('public.competitions.index'), 'lastmod' => now()],
            ['loc' => route('result.index'), 'lastmod' => now()],
        ])->merge(Competition::query()->publiclyVisible()->get(['public_slug', 'updated_at'])->map(fn ($competition) => [
            'loc' => route('public.competitions.show', $competition),
            'lastmod' => $competition->updated_at,
        ]));

        return response(view('public.sitemap', compact('urls'))->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
