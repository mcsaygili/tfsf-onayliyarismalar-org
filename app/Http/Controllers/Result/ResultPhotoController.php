<?php

namespace App\Http\Controllers\Result;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSubmissionPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultPhotoController extends Controller
{
    public function __invoke(CompetitionSubmissionPhoto $submissionPhoto): StreamedResponse
    {
        $submissionPhoto->loadMissing('submission.entry.competition');
        $competition = $submissionPhoto->submission->entry->competition;
        $allowed = ! $submissionPhoto->withdrawn_at
            && $competition->newQuery()->whereKey($competition->getKey())->publiclyVisible()->exists()
            && $competition->results_published_at?->lte(now())
            && $submissionPhoto->results()->whereHas('awards')->whereHas('round', fn ($query) => $query->where('is_final', true)->orWhere('status', 'finalized'))->exists();
        abort_unless($allowed, 404);
        $path = $submissionPhoto->jury_path ?: $submissionPhoto->disk_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
