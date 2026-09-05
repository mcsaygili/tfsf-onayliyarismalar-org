<?php

namespace Tests\Feature\Juri;

use App\Models\Juri;
use App\Models\JuryTag;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class JuryTagConcurrencyTest extends TestCase
{
    use CreatesSecuritySubmission, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This process concurrency test requires MariaDB/MySQL.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName(), 'Use an isolated tfsf_testing database.');
        $this->assertTrue(app()->environment('testing'));
    }

    public function test_simultaneous_creation_and_attachment_do_not_duplicate_private_tags(): void
    {
        $submission = $this->securitySubmission();
        $competition = $submission->entry->competition;
        $competition->update(['application_ends_at' => now()->subDays(2), 'evaluation_starts_at' => now()->subDay(), 'evaluation_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
        $juror = Juri::factory()->create();
        $category = $submission->category;
        $category->jurorAssignments()->create(['juror_id' => $juror->id]);
        $photo = $submission->photos()->create([
            'disk_path' => 'private-test/'.Str::uuid().'.jpg', 'original_filename' => 'test.jpg',
            'mime_type' => 'image/jpeg', 'file_size_bytes' => 100, 'sha256' => hash('sha256', Str::uuid()),
        ]);
        $auth = ['guard' => 'juri', 'user_id' => $juror->id, 'capture_json' => true];
        $results = $this->simultaneousRequests($auth + ['url' => route('juri.tags.store', [$competition, $category]), 'method' => 'POST', 'payload' => ['name' => 'Favoriler', 'color' => '#123456']]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertDatabaseCount('jury_tags', 1);
        $tag = JuryTag::firstOrFail();
        $this->assertSame($juror->id, $tag->juror_id);
        $this->assertSame([$tag->id, $tag->id], array_map(fn ($result) => $result['json']['tag']['id'], $results));
        $results = $this->simultaneousRequests($auth + ['url' => route('juri.tags.attach', [$competition, $category, $tag, $photo]), 'method' => 'PUT']);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertSame([[$photo->id], [$photo->id]], array_map(fn ($result) => $result['json']['tag']['photo_ids'], $results));
        $this->assertDatabaseCount('jury_tag_photos', 1);
        $this->assertDatabaseHas('jury_tag_photos', ['jury_tag_id' => $tag->id, 'submission_photo_id' => $photo->id]);
    }
}
