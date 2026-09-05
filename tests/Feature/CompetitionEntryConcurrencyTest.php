<?php

namespace Tests\Feature;

use App\Models\MemberGroup;
use App\Models\Photo;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\MemberEligibilityService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\Concerns\RunsConcurrentAccountRequests;
use Tests\TestCase;

#[Group('mysql-concurrency')]
class CompetitionEntryConcurrencyTest extends TestCase
{
    use CreatesSecuritySubmission, DatabaseMigrations, RunsConcurrentAccountRequests;

    private string $storageRoot;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This process concurrency test requires MariaDB/MySQL.');
        }
        $this->assertSame('tfsf_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(app()->environment('testing'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = sys_get_temp_dir().'/tfsf-entry-'.Str::uuid();
        config(['filesystems.disks.local.root' => $this->storageRoot.'/local', 'filesystems.disks.public.root' => $this->storageRoot.'/public']);
        Storage::forgetDisk(['local', 'public']);
    }

    protected function tearDown(): void
    {
        if (isset($this->storageRoot)) {
            File::deleteDirectory($this->storageRoot);
        }
        parent::tearDown();
    }

    public function test_two_different_photos_cannot_take_the_same_last_slot(): void
    {
        $submission = $this->securitySubmission();
        $submission->category->update(['max_photos_per_participant' => 1]);
        $first = $this->source($submission->entry->user_id, 'first');
        $second = $this->source($submission->entry->user_id, 'second');
        $base = $this->request($submission->entry->user_id, route('competitions.submission.portfolio.store', $submission), 'POST');
        $results = $this->simultaneousRequests($base + ['payload' => ['photo_id' => $first->id]], $base + ['payload' => ['photo_id' => $second->id]]);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertSame(1, $submission->activePhotos()->count());
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_duplicate_requests_return_validation_instead_of_unique_constraint_failure(): void
    {
        $submission = $this->securitySubmission();
        $photo = $this->source($submission->entry->user_id, 'same');
        $input = $this->request($submission->entry->user_id, route('competitions.submission.portfolio.store', $submission), 'POST') + ['payload' => ['photo_id' => $photo->id]];
        $results = $this->simultaneousRequests($input);
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $this->assertDatabaseCount('competition_submission_photos', 1);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_last_photo_removal_and_submission_cannot_both_succeed(): void
    {
        $submission = $this->securitySubmission();
        $entry = $submission->entry;
        $entry->user->update(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $submission->category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $this->assertTrue(app(MemberEligibilityService::class)->forCategory($submission->category->fresh(), $entry->user->fresh())['eligible']);
        $source = $this->source($entry->user_id, 'submit-race');
        $photo = app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);
        $results = $this->simultaneousRequests(
            $this->request($entry->user_id, route('competitions.entry.submit', $entry), 'POST') + ['payload' => ['consent' => true]],
            $this->request($entry->user_id, route('competitions.submission.photos.destroy', $photo), 'DELETE'),
        );
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        if (! $results[0]['errors']) {
            $this->assertSame('approved', $entry->fresh()->status->value);
            $this->assertModelExists($photo);
            Storage::disk('local')->assertExists([$photo->disk_path, $photo->jury_path]);
        } else {
            $this->assertSame('draft', $entry->fresh()->status->value);
            $this->assertDatabaseCount('competition_submission_photos', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_category_addition_and_submission_cannot_leave_an_approved_empty_category(): void
    {
        $submission = $this->securitySubmission();
        $entry = $submission->entry;
        $entry->user->update(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $submission->category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $this->assertTrue(app(MemberEligibilityService::class)->forCategory($submission->category->fresh(), $entry->user->fresh())['eligible']);
        $source = $this->source($entry->user_id, 'category-race');
        app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);
        $category = $entry->competition->categories()->create(['sort_order' => 20]);
        $category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $this->assertTrue(app(MemberEligibilityService::class)->forCategory($category->fresh(), $entry->user->fresh())['eligible']);
        $results = $this->simultaneousRequests(
            $this->request($entry->user_id, route('competitions.entry.submit', $entry), 'POST') + ['payload' => ['consent' => true]],
            $this->request($entry->user_id, route('competitions.entry.categories.store', $entry), 'POST') + ['payload' => ['category_id' => $category->id]],
        );
        $this->assertSame([302, 302], array_column($results, 'status'));
        $this->assertSame(1, count(array_filter(array_column($results, 'errors'))));
        $submitted = ! $results[0]['errors'];
        $this->assertSame($submitted ? 'approved' : 'draft', $entry->fresh()->status->value);
        $this->assertSame($submitted ? 1 : 2, $entry->submissions()->count());
        $this->assertSame($submitted ? 1 : 0, $entry->events()->where('event', 'submitted')->count());
    }

    private function source(string $userId, string $suffix): Photo
    {
        $photo = Photo::factory()->create(['user_id' => $userId]);
        Storage::disk('public')->put($photo->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')).$suffix);

        return $photo;
    }

    private function request(string $userId, string $url, string $method): array
    {
        return ['guard' => 'web', 'user_id' => $userId, 'url' => $url, 'method' => $method, 'storage_root' => $this->storageRoot];
    }
}
