<?php

namespace App\Console\Commands;

use App\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditResultArchives extends Command
{
    protected $signature = 'tfsf:audit-result-archives {--competition= : Only this competition UUID} {--verify-files : Read and verify archived image checksums}';

    protected $description = 'Report missing or incomplete result archives without modifying data';

    public function handle(): int
    {
        $issues = 0;
        Competition::query()->whereNotNull('results_published_at')->when($this->option('competition'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')->chunkById(100, function ($competitions) use (&$issues): void {
                foreach ($competitions as $competition) {
                    $publication = $competition->resultPublications()->where('version', $competition->results_publication_version)->first();
                    $problems = [];
                    if (! $publication) {
                        $problems[] = 'missing_publication';
                    } else {
                        if ($publication->snapshot_version < 2) {
                            $problems[] = 'legacy_partial_snapshot';
                        }
                        if ($publication->withdrawn_at) {
                            $problems[] = 'current_publication_withdrawn';
                        }
                        $assets = $publication->assets->keyBy('source_photo_id');
                        $expected = collect($publication->snapshot['results'] ?? [])->filter(fn ($row) => ! empty($row['awards']));
                        $expected = $expected->concat(collect($publication->snapshot['member_entries'] ?? [])->flatMap(fn ($entry) => $entry['photos']))->unique('photo_id');
                        foreach ($expected as $row) {
                            $asset = $assets->get($row['photo_id']);
                            if (! $asset || ! Storage::disk('local')->exists($asset->disk_path)) {
                                $problems[] = 'missing_image:'.$row['photo_id'];
                            } elseif ($this->option('verify-files') && ! hash_equals($asset->sha256, hash_file('sha256', Storage::disk('local')->path($asset->disk_path)))) {
                                $problems[] = 'checksum_mismatch:'.$row['photo_id'];
                            }
                        }
                    }
                    if ($problems) {
                        $issues++;
                        $this->line($competition->id.' '.implode(', ', $problems));
                    }
                }
            });
        $this->line('Competitions requiring archive review: '.$issues);

        return $issues ? self::FAILURE : self::SUCCESS;
    }
}
