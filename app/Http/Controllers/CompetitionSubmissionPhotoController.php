<?php

namespace App\Http\Controllers;

use App\Models\CompetitionSubmissionPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetitionSubmissionPhotoController extends Controller
{
    public function __invoke(Request $request, CompetitionSubmissionPhoto $submissionPhoto): StreamedResponse
    {
        $submissionPhoto->loadMissing('submission.entry.competition');
        $submission = $submissionPhoto->submission;
        $entry = $submission->entry;
        $competition = $entry->competition;

        $allowed = match (true) {
            $request->routeIs('competitions.photos.show') => Auth::guard('web')->check()
                && ($entry->user_id === Auth::guard('web')->id() || filled($competition->results_published_at)),
            $request->routeIs('institution.participant-submissions.photos.show') => Auth::guard('institution')->check()
                && $competition->institution_id === Auth::guard('institution')->user()->institution_id,
            $request->routeIs('temsilci.participant-submissions.photos.show') => Auth::guard('temsilci')->check()
                && $competition->representative_id === Auth::guard('temsilci')->id(),
            $request->routeIs('juri.evaluations.photos.show') => Auth::guard('juri')->check()
                && $submission->category->jurorAssignments()->where('juror_id', Auth::guard('juri')->id())->exists(),
            default => false,
        };

        abort_unless($allowed, 404);

        $path = $submissionPhoto->jury_path ?: $submissionPhoto->disk_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
