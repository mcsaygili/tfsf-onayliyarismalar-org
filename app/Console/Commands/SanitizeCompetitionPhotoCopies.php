<?php

namespace App\Console\Commands;

use App\Models\CompetitionSubmissionPhoto;
use App\Services\JuryPhotoRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SanitizeCompetitionPhotoCopies extends Command
{
    protected $signature = 'photos:sanitize-competition-copies {--dry-run : Count copies requiring sanitization}';

    protected $description = 'Rebuild unverified jury copies without identity or location metadata';

    public function handle(JuryPhotoRenderer $renderer): int
    {
        $query = CompetitionSubmissionPhoto::query()->whereNull('jury_sanitized_at');
        if ($this->option('dry-run')) {
            $this->info('Copies requiring sanitization: '.$query->count());

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;
        $query->chunkById(100, function ($photos) use ($renderer, &$processed, &$failed): void {
            foreach ($photos as $candidate) {
                $newPath = null;
                try {
                    $changed = DB::transaction(function () use ($candidate, $renderer, &$newPath): bool {
                        $photo = CompetitionSubmissionPhoto::whereKey($candidate->id)->lockForUpdate()->first();
                        if (! $photo || $photo->jury_sanitized_at) {
                            return false;
                        }
                        $disk = Storage::disk('local');
                        $bytes = $disk->get($photo->disk_path);
                        $safeBytes = $renderer->render($bytes);
                        $newPath = dirname($photo->disk_path).'/'.Str::uuid().'-jury.jpg';
                        if (! $disk->put($newPath, $safeBytes)) {
                            throw new \RuntimeException('Unable to store sanitized copy.');
                        }
                        $photo->forceFill(['jury_path' => $newPath, 'jury_sanitized_at' => now()])->save();

                        return true;
                    });
                    $processed += (int) $changed;
                } catch (\Throwable) {
                    if ($newPath) {
                        Storage::disk('local')->delete($newPath);
                    }
                    $failed++;
                    // IDs permit investigation without exposing names, paths or metadata.
                    $this->error('Could not sanitize photo '.$candidate->id);
                }
            }
        });
        $this->info("Sanitized: {$processed}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
