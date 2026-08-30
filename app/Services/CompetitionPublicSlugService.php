<?php

namespace App\Services;

use App\Models\Competition;
use Illuminate\Support\Str;

class CompetitionPublicSlugService
{
    public function ensure(Competition $competition): string
    {
        if (filled($competition->public_slug)) {
            return $competition->public_slug;
        }

        $competition->loadMissing('translations');
        $name = $competition->getTranslation(config('locales.default'), false)?->name
            ?? $competition->translations->first()?->name
            ?? 'tfsf-yarismasi';
        $base = Str::limit(Str::slug($name), 160, '') ?: 'tfsf-yarismasi';
        $slug = $base;
        $suffix = 2;

        while (Competition::query()->where('public_slug', $slug)->where('id', '!=', $competition->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $competition->forceFill(['public_slug' => $slug])->saveQuietly();

        return $slug;
    }
}
