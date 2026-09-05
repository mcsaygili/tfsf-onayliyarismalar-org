<?php

namespace App\Http\Controllers\Result;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionResultAsset;
use App\Models\CompetitionResultPublication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultPhotoController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $photoId = $request->route('photoId') ?? $request->route('submissionPhoto');
        $publication = $request->route('publication');
        $publicationId = $publication instanceof CompetitionResultPublication ? $publication->id : $publication;
        $query = CompetitionResultAsset::query()->where('source_photo_id', $photoId);
        if ($request->routeIs('eys.competitions.publication-photos.show')) {
            $competition = $request->route('competition');
            $competition = $competition instanceof Competition ? $competition : Competition::findOrFail($competition);
            $query->where('publication_id', $publicationId)->whereHas('publication', fn ($q) => $q->where('competition_id', $competition->id));
        } elseif ($request->routeIs('competitions.result-photos.show')) {
            $competition = $request->route('competition');
            $competition = $competition instanceof Competition ? $competition : Competition::findOrFail($competition);
            $query->where('publication_id', $publicationId)->where('owner_user_id', Auth::guard('web')->id())
                ->whereHas('publication', fn ($q) => $q->currentPublic()->where('competition_id', $competition->id));
        } else {
            $query->where('is_public', true)->whereHas('publication', fn ($q) => $q->currentPublic());
            if ($publicationId) {
                $query->where('publication_id', $publicationId);
            }
        }
        $asset = $query->firstOrFail();
        $disk = Storage::disk('local');
        abort_unless($disk->exists($asset->disk_path), 404);
        $stream = $disk->readStream($asset->disk_path);
        abort_unless(is_resource($stream), 404);
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        if (! hash_equals($asset->sha256, hash_final($hash))) {
            fclose($stream);
            abort(404);
        }
        rewind($stream);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => $asset->mime_type, 'Content-Disposition' => 'inline', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
