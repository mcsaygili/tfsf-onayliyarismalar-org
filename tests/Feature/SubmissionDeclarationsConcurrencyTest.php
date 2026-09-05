<?php

namespace Tests\Feature;

use App\Models\MemberGroup;
use App\Services\MemberEligibilityService;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class SubmissionDeclarationsConcurrencyTest extends TestCase
{
    use CreatesSecuritySubmission, DatabaseMigrations, RunsConcurrentAccountRequests;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MariaDB/MySQL process isolation.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    private function context(): array
    {
        $submission = $this->securitySubmission();
        $submission->entry->user->update(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $submission->category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $photo = $submission->photos()->create(['disk_path' => 'unused-declaration-test.jpg', 'original_filename' => 'test.jpg', 'mime_type' => 'image/jpeg',
            'sha256' => hash('sha256', 'declaration fixture'), 'file_size_bytes' => 10, 'sort_order' => 10,
            'declaration' => ['title' => 'İlk ad', 'location' => 'İzmir', 'taken_on' => '2024-01-01', 'story' => null]]);
        $this->assertTrue(app(MemberEligibilityService::class)->forCategory($submission->category->fresh(), $submission->entry->user->fresh())['eligible']);
        SubmissionDeclarations::assertComplete($submission->fresh());

        return [$submission, $photo];
    }

    private function request($submission, $photo, ?string $title): array
    {
        return ['guard' => 'web', 'user_id' => $submission->entry->user_id, 'method' => 'PUT',
            'url' => route('competitions.submission.details.update', $submission),
            'payload' => ['details_version' => 0, 'category_story' => null,
                'photos' => [['id' => $photo->id, ...$photo->declarationData(), 'title' => $title]]]];
    }

    public function test_two_concurrent_editors_cannot_overwrite_each_other(): void
    {
        [$submission, $photo] = $this->context();
        $results = $this->simultaneousRequests($this->request($submission, $photo, 'Birinci'), $this->request($submission, $photo, 'İkinci'));
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $winner = $results[0]['errors'] ? 'İkinci' : 'Birinci';
        $this->assertSame($winner, $photo->fresh()->declarationData()['title']);
        $this->assertSame(1, $submission->fresh()->details_version);
        $this->assertSame(1, $submission->entry->events()->where('event', 'submission_details_updated')->count());
    }

    public function test_clearing_a_required_field_and_submitting_cannot_both_succeed(): void
    {
        [$submission, $photo] = $this->context();
        $entry = $submission->entry;
        $results = $this->simultaneousRequests($this->request($submission, $photo, null), [
            'guard' => 'web', 'user_id' => $entry->user_id, 'method' => 'POST',
            'url' => route('competitions.entry.submit', $entry), 'payload' => ['consent' => true],
        ]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        if ($results[0]['errors']) {
            $this->assertSame('approved', $entry->fresh()->status->value);
            $this->assertSame('İlk ad', $photo->fresh()->declarationData()['title']);
        } else {
            $this->assertSame('draft', $entry->fresh()->status->value);
            $this->assertNull($photo->fresh()->declarationData()['title']);
            $this->assertNull($entry->fresh()->submitted_at);
        }
    }
}
