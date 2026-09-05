<?php

namespace Tests\Feature;

use App\Models\CompetitionEntryEvent;
use App\Models\Juri;
use App\Models\JuryEvaluationSubmission;
use App\Models\MemberGroup;
use App\Models\Photo;
use App\Models\User;
use App\Services\CompetitionEntryService;
use App\Services\CompetitionSubmissionDetailsService;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\JuryEvaluationService;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesSecuritySubmission;
use Tests\TestCase;

class SubmissionDeclarationsTest extends TestCase
{
    use CreatesSecuritySubmission, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    private function context(): array
    {
        $submission = $this->securitySubmission();
        $submission->entry->user->update(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $submission->category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->sole()->id]);
        $source = Photo::factory()->create(['user_id' => $submission->entry->user_id, 'title' => 'Kıyı', 'location' => 'İzmir', 'taken_at' => '2024-03-04', 'exif_captured_at' => '2023-01-02 10:20:30']);
        Storage::disk('public')->put($source->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')));
        $photo = app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);

        return [$submission->fresh(), $photo, $source];
    }

    private function payload($submission, $photo, array $changes = []): array
    {
        return ['details_version' => $submission->details_version, 'details_submission_id' => $submission->id, 'category_story' => null,
            'photos' => [['id' => $photo->id, ...$photo->declarationData(), ...$changes]]];
    }

    private function putDetails($submission, array $payload)
    {
        return $this->actingAs($submission->entry->user)->put(route('competitions.submission.details.update', $submission), $payload);
    }

    public function test_portfolio_declaration_is_independent_of_exif_and_source_edits(): void
    {
        [$submission, $photo, $source] = $this->context();
        $snapshot = $photo->metadata_snapshot;
        $this->assertSame('2024-03-04', $photo->declarationData()['taken_on']);
        $this->putDetails($submission, $this->payload($submission, $photo, ['title' => 'Yeni ad', 'taken_on' => '2025-06-07']))->assertSessionHasNoErrors();
        $this->assertSame('2025-06-07', $photo->fresh()->declarationData()['taken_on']);
        $this->assertEquals($snapshot, $photo->fresh()->metadata_snapshot);
        $this->assertSame('Kıyı', $source->fresh()->title);
        $source->update(['title' => 'Portföy değişti', 'taken_at' => '2020-01-01']);
        $source->delete();
        $this->assertSame('Yeni ad', $photo->fresh()->declarationData()['title']);
        Storage::disk('local')->assertExists($photo->disk_path);
    }

    public function test_incomplete_draft_is_saved_but_cannot_be_submitted(): void
    {
        [$submission, $photo] = $this->context();
        $this->putDetails($submission, $this->payload($submission, $photo, ['title' => '   ', 'taken_on' => null]))->assertSessionHasNoErrors();
        $this->assertNull($photo->fresh()->declarationData()['title']);
        $this->post(route('competitions.entry.submit', $submission->entry), ['consent' => true])->assertSessionHasErrors('entry');
        $this->assertSame('draft', $submission->fresh()->status->value);
        $this->assertNull($submission->entry->fresh()->submitted_at);
    }

    public function test_required_photo_and_category_stories_are_checked_at_final_submission(): void
    {
        [$submission, $photo] = $this->context();
        $submission->category->update(['photo_story_required' => true, 'category_story_required' => true]);
        $this->actingAs($submission->entry->user)->post(route('competitions.entry.submit', $submission->entry), ['consent' => true])->assertSessionHasErrors('entry');
        $payload = $this->payload($submission, $photo, ['story' => 'Eserin hikâyesi']);
        $payload['category_story'] = 'Ortak hikâye';
        $this->putDetails($submission, $payload)->assertSessionHasNoErrors();
        $this->post(route('competitions.entry.submit', $submission->entry), ['consent' => true])->assertSessionHasNoErrors();
        $this->assertSame('approved', $submission->fresh()->status->value);
    }

    public static function malformedFields(): array
    {
        return [
            'invalid calendar date' => [['taken_on' => '2025-02-30']],
            'ambiguous date' => [['taken_on' => '03/04/2025']],
            'invalid type' => [['title' => ['nested']]],
            'oversized title' => [['title' => str_repeat('a', 256)]],
            'oversized story' => [['story' => str_repeat('ç', 4001)]],
            'untrusted metadata key' => [['exif_captured_at' => '2026-01-01']],
        ];
    }

    #[DataProvider('malformedFields')]
    public function test_invalid_declarations_do_not_change_any_records(array $fields): void
    {
        [$submission, $photo] = $this->context();
        $before = $photo->declarationData();
        $this->putDetails($submission, $this->payload($submission, $photo, $fields))->assertSessionHasErrors();
        $this->assertSame($before, $photo->fresh()->declarationData());
        $this->assertSame(1, $submission->fresh()->details_version);
    }

    public function test_same_version_cannot_overwrite_a_saved_declaration(): void
    {
        [$submission, $photo] = $this->context();
        $first = $this->payload($submission, $photo, ['title' => 'Birinci sekme']);
        $second = $this->payload($submission, $photo, ['title' => 'Eski sekme']);
        $this->putDetails($submission, $first)->assertSessionHasNoErrors();
        $this->putDetails($submission, $second)->assertSessionHasErrors('details');
        $this->assertSame('Birinci sekme', $photo->fresh()->declarationData()['title']);
    }

    public function test_foreign_or_missing_photo_set_and_foreign_owner_are_rejected(): void
    {
        [$submission, $photo] = $this->context();
        $payload = $this->payload($submission, $photo);
        $payload['photos'][0]['id'] = (string) Str::uuid();
        $this->putDetails($submission, $payload)->assertSessionHasErrors('details');
        $this->putDetails($submission, ['details_version' => 1, 'photos' => []])->assertSessionHasErrors('photos');
        $this->actingAs(User::factory()->create())->put(route('competitions.submission.details.update', $submission), $this->payload($submission, $photo))->assertNotFound();
        $this->assertSame('Kıyı', $photo->fresh()->declarationData()['title']);
    }

    public function test_uploading_or_deleting_a_photo_invalidates_the_details_form(): void
    {
        [$submission, $photo] = $this->context();
        $payload = $this->payload($submission, $photo);
        $source = Photo::factory()->create(['user_id' => $submission->entry->user_id]);
        Storage::disk('public')->put($source->disk_path, file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')).'second');
        $second = app(CompetitionSubmissionPhotoService::class)->fromPortfolio($submission, $source, null, []);
        $this->putDetails($submission, $payload)->assertSessionHasErrors('details');
        app(CompetitionSubmissionPhotoService::class)->remove($second);
        $this->putDetails($submission, $payload)->assertSessionHasErrors('details');
        $this->assertSame(3, $submission->fresh()->details_version);
        $this->assertSame('Kıyı', $photo->fresh()->declarationData()['title']);

    }

    public function test_photo_order_is_saved_atomically_and_duplicate_positions_are_rejected(): void
    {
        [$submission, $first] = $this->context();
        $submission->category->update(['photo_order_required' => true]);
        $second = $submission->photos()->create(['disk_path' => 'test-only.jpg', 'original_filename' => 'test-only.jpg', 'mime_type' => 'image/jpeg',
            'sha256' => hash('sha256', 'second'), 'file_size_bytes' => 10, 'sort_order' => 20, 'declaration' => $first->declarationData()]);
        $payload = $this->payload($submission, $first, ['position' => 2]);
        $payload['photos'][] = ['id' => $second->id, ...$second->declarationData(), 'position' => 1];
        $this->putDetails($submission, $payload)->assertSessionHasNoErrors();
        $this->assertSame([$second->id, $first->id], $submission->activePhotos()->pluck('id')->all());
        $payload['details_version'] = $submission->fresh()->details_version;
        $payload['photos'][1]['position'] = 2;
        $this->putDetails($submission, $payload)->assertSessionHasErrors();
        $this->assertSame([$second->id, $first->id], $submission->activePhotos()->pluck('id')->all());
    }

    public function test_event_failure_rolls_back_all_photo_fields_and_version(): void
    {
        [$submission, $photo] = $this->context();
        Event::listen('eloquent.creating: '.CompetitionEntryEvent::class, fn () => throw new \RuntimeException('Injected details event failure'));
        try {
            app(CompetitionSubmissionDetailsService::class)->update($submission, $submission->entry->user, 1, $this->payload($submission, $photo, ['title' => 'Rolled back']));
            $this->fail('The injected event failure must abort the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected details event failure', $exception->getMessage());
        }
        $this->assertSame('Kıyı', $photo->fresh()->declarationData()['title']);
        $this->assertSame(1, $submission->fresh()->details_version);
    }

    public function test_submitted_details_are_locked_until_an_allowed_revision_period(): void
    {
        [$submission, $photo] = $this->context();
        app(CompetitionEntryService::class)->submit($submission->entry);
        $this->putDetails($submission, $this->payload($submission, $photo, ['title' => 'Not allowed']))->assertSessionHasErrors('details');
        $this->assertSame('Kıyı', $photo->fresh()->declarationData()['title']);
    }

    public function test_evaluation_revision_reopens_completion_and_story_output_is_escaped(): void
    {
        [$submission, $photo] = $this->context();
        $competition = $submission->entry->competition;
        $competition->update(['application_ends_at' => now()->subDay(), 'evaluation_starts_at' => now()->subHour(), 'evaluation_ends_at' => now()->addDay(), 'competition_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
        $submission->category->update(['photo_story_required' => true, 'category_story_required' => true]);
        $juror = Juri::factory()->create();
        $assignment = $submission->category->jurorAssignments()->create(['juror_id' => $juror->id]);
        $round = app(JuryEvaluationService::class)->roundFor($competition);
        JuryEvaluationSubmission::create(['competition_evaluation_round_id' => $round->id, 'juror_assignment_id' => $assignment->id, 'finalized_at' => now()]);
        $story = '<script>alert("story")</script> Eser anlatısı';
        $payload = $this->payload($submission, $photo, ['story' => $story]);
        $payload['category_story'] = 'Ortak kategori anlatısı';
        $this->putDetails($submission, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
        $this->actingAs($juror, 'juri')->get(route('juri.evaluations.show', [$competition, $submission->category]))->assertOk()
            ->assertSee($story)->assertSee('Ortak kategori anlatısı')->assertDontSee($story, false)
            ->assertDontSee($submission->entry->user->email)->assertDontSee('TFSF_AUDIT_PHOTOGRAPHER');
    }

    public function test_exif_timestamp_alone_does_not_satisfy_the_declared_date(): void
    {
        $data = SubmissionDeclarations::fromMetadata(['title' => 'Title', 'location' => 'Location', 'exif_captured_at' => '2024-01-01 12:00:00']);
        $this->assertNull($data['taken_on']);
    }

    public function test_evaluation_upload_requires_declarations_and_keeps_exif_separate(): void
    {
        [$submission] = $this->context();
        $submission->entry->competition->update(['application_ends_at' => now()->subDay(), 'evaluation_starts_at' => now()->subHour(),
            'evaluation_ends_at' => now()->addDay(), 'competition_ends_at' => now()->addDay()]);
        $submission->update(['status' => 'approved']);
        $file = fn () => UploadedFile::fake()->createWithContent('revision.jpg', file_get_contents(base_path('tests/Fixtures/identity-metadata.jpg')).'revision');
        $url = route('competitions.submission.upload', $submission);
        $this->actingAs($submission->entry->user)->post($url, ['photo' => $file()])->assertSessionHasErrors();
        $this->assertSame(1, $submission->photos()->count());
        $this->post($url, ['photo' => $file(), 'declaration' => ['title' => 'Yeni eser', 'location' => 'Bursa', 'taken_on' => '2019-05-06']])->assertSessionHasNoErrors();
        $uploaded = $submission->photos()->where('original_filename', 'revision.jpg')->sole();
        $this->assertSame('2019-05-06', $uploaded->declarationData()['taken_on']);
        $this->assertArrayNotHasKey('taken_at', $uploaded->metadata_snapshot);
        $this->assertSame(2, $submission->fresh()->details_version);
    }
}
