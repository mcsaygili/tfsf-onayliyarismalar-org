<?php

namespace Tests\Feature;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionEntryStatus;
use App\Enums\CompetitionStatus;
use App\Enums\CompetitionSubmissionStatus;
use App\Enums\Module;
use App\Models\AgeEligibilityRule;
use App\Models\AwardReference;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\EvaluationCriterion;
use App\Models\EysUser;
use App\Models\Juri;
use App\Models\MemberGroup;
use App\Models\ParticipantApprovalProcess;
use App\Models\ParticipantGender;
use App\Models\Permission;
use App\Models\Photo;
use App\Models\ProcessingMethod;
use App\Models\User;
use App\Notifications\Juri\CompetitionResultsPublishedNotification as JuryResultsPublishedNotification;
use App\Notifications\Uye\CompetitionResultsPublishedNotification as MemberResultsPublishedNotification;
use Database\Seeders\AwardReferenceSeeder;
use Database\Seeders\CompetitionCategoryReferenceSeeder;
use Database\Seeders\EvaluationCriterionSeeder;
use Database\Seeders\ParticipantApprovalProcessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompetitionParticipationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->seed([
            CompetitionCategoryReferenceSeeder::class,
            AwardReferenceSeeder::class,
            EvaluationCriterionSeeder::class,
            ParticipantApprovalProcessSeeder::class,
        ]);
    }

    public function test_member_catalogue_only_lists_published_approved_competitions(): void
    {
        $user = $this->member();
        $visible = $this->competition();
        $draft = Competition::factory()->create();
        $unpublished = Competition::factory()->create(['status' => CompetitionStatus::Approved, 'published_at' => null]);

        $response = $this->actingAs($user)->get(route('competitions.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('competitions')->contains($visible));
        $this->assertFalse($response->viewData('competitions')->contains($draft));
        $this->assertFalse($response->viewData('competitions')->contains($unpublished));
    }

    public function test_incomplete_profile_cannot_start_an_entry_and_receives_action_required_state(): void
    {
        $user = User::factory()->create(['gender' => null, 'date_of_birth' => null]);
        $competition = $this->competition();

        $detail = $this->actingAs($user)->get(route('competitions.show', $competition));
        $detail->assertOk();
        $this->assertSame('action_required', $detail->viewData('competitionCheck')['state']);

        $this->actingAs($user)
            ->post(route('competitions.start', $competition))
            ->assertSessionHasErrors('competition');

        $this->assertDatabaseCount('competition_entries', 0);
    }

    public function test_portfolio_photo_is_copied_to_an_immutable_submission_and_entry_is_locked_after_submit(): void
    {
        $user = $this->member();
        $competition = $this->competition();
        $category = $this->category($competition);
        $source = Photo::factory()->for($user)->create([
            'disk_path' => 'portfolio/source.jpg',
            'thumb_path' => null,
        ]);
        Storage::disk('public')->put($source->disk_path, $this->fixture());

        $entry = $this->startEntry($user, $competition, $category);
        $submission = $entry->submissions()->firstOrFail();

        $this->actingAs($user)->post(route('competitions.submission.portfolio.store', $submission), [
            'photo_id' => $source->id,
        ])->assertRedirect();

        $submissionPhoto = $submission->photos()->firstOrFail();
        $this->assertSame($source->id, $submissionPhoto->source_photo_id);
        $this->assertSame(hash('sha256', $this->fixture()), $submissionPhoto->sha256);
        Storage::disk('local')->assertExists($submissionPhoto->disk_path);
        Storage::disk('local')->assertExists($submissionPhoto->jury_path);

        $this->actingAs($user)->get(route('competitions.photos.show', $submissionPhoto))->assertOk();
        $this->actingAs($this->member())->get(route('competitions.photos.show', $submissionPhoto))->assertNotFound();

        Storage::disk('public')->delete($source->disk_path);
        $source->delete();
        Storage::disk('local')->assertExists($submissionPhoto->disk_path);

        $this->actingAs($user)->post(route('competitions.entry.submit', $entry), ['consent' => '1'])
            ->assertRedirect(route('competitions.entry.show', $entry));

        $this->assertSame(CompetitionEntryStatus::Approved, $entry->refresh()->status);
        $this->assertSame(CompetitionSubmissionStatus::Approved, $submission->refresh()->status);
        $this->assertNotNull($entry->consent_at);

        $this->actingAs($user)
            ->delete(route('competitions.submission.photos.destroy', $submissionPhoto))
            ->assertSessionHasErrors('photo');
        $this->assertModelExists($submissionPhoto);

        $anotherCategory = $this->category($competition);
        $this->actingAs($user)
            ->post(route('competitions.entry.categories.store', $entry), ['category_id' => $anotherCategory->id])
            ->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('competition_submissions', [
            'competition_entry_id' => $entry->id,
            'competition_category_id' => $anotherCategory->id,
        ]);
    }

    public function test_institution_approval_is_scoped_and_updates_submission_and_entry(): void
    {
        $user = $this->member();
        $process = ParticipantApprovalProcess::where('code', 'institution')->firstOrFail();
        $competition = $this->competition(['participant_approval_process_id' => $process->id]);
        $category = $this->category($competition);
        [$entry, $submission] = $this->submittedEntry($user, $competition, $category);
        $approval = $submission->approvals()->firstOrFail();

        $this->assertSame(CompetitionEntryStatus::PendingApproval, $entry->refresh()->status);
        $this->assertSame(CompetitionSubmissionStatus::PendingApproval, $submission->refresh()->status);

        $otherCompetition = $this->competition();
        $this->actingAs($otherCompetition->institutionStaff, 'institution')
            ->get(route('institution.participant-submissions.show', $approval))
            ->assertNotFound();

        $this->actingAs($competition->institutionStaff, 'institution')
            ->get(route('institution.participant-submissions.photos.show', $submission->photos()->firstOrFail()))
            ->assertOk();

        $this->actingAs($competition->institutionStaff, 'institution')
            ->post(route('institution.participant-submissions.decide', $approval), ['decision' => 'approve'])
            ->assertRedirect(route('institution.participant-submissions.index'));

        $this->assertSame(CompetitionSubmissionStatus::Approved, $submission->refresh()->status);
        $this->assertSame(CompetitionEntryStatus::Approved, $entry->refresh()->status);
        $this->assertNotNull($approval->refresh()->reviewed_at);
        $this->assertDatabaseHas('competition_entry_events', ['competition_entry_id' => $entry->id, 'event' => 'submission_approved']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id, 'notifiable_type' => User::class]);

        $notification = $user->notifications()->firstOrFail();
        $this->actingAs($this->member())
            ->get(route('notifications.show', $notification->id))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('uye.notifications.submission_decision_title'));
        $this->actingAs($user)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('competitions.entry.show', $entry));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_juror_can_only_score_between_three_and_nine_and_finalization_locks_scores(): void
    {
        $user = $this->member();
        $competition = $this->competition();
        $category = $this->category($competition);
        [, $submission] = $this->submittedEntry($user, $competition, $category);
        $photo = $submission->photos()->firstOrFail();
        $criterion = $category->evaluationCriteria()->firstOrFail();
        $juror = Juri::factory()->create();
        $category->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $this->openEvaluation($competition);

        $this->actingAs($juror, 'juri')->get(route('juri.evaluations.photos.show', $photo))->assertOk();
        $this->actingAs(Juri::factory()->create(), 'juri')->get(route('juri.evaluations.photos.show', $photo))->assertNotFound();

        $invalidScores = ['scores' => [$photo->id => [$criterion->id => 2]]];
        $this->actingAs($juror, 'juri')
            ->put(route('juri.evaluations.save', [$competition, $category]), $invalidScores)
            ->assertSessionHasErrors('scores');

        $validScores = ['scores' => [$photo->id => [$criterion->id => 9]]];
        $this->actingAs($juror, 'juri')
            ->put(route('juri.evaluations.finalize', [$competition, $category]), $validScores)
            ->assertRedirect();

        $this->assertDatabaseHas('jury_scores', ['submission_photo_id' => $photo->id, 'score' => 9]);
        $this->assertDatabaseCount('jury_evaluation_submissions', 1);

        $this->actingAs($juror, 'juri')
            ->put(route('juri.evaluations.save', [$competition, $category]), [
                'scores' => [$photo->id => [$criterion->id => 3]],
            ])->assertSessionHasErrors('scores');
        $this->assertDatabaseHas('jury_scores', ['submission_photo_id' => $photo->id, 'score' => 9]);
    }

    public function test_eys_can_publish_results_only_after_all_jurors_finalize_and_member_sees_ranking(): void
    {
        $user = $this->member();
        $secondUser = $this->member();
        $competition = $this->competition();
        $category = $this->category($competition);
        [, $submission] = $this->submittedEntry($user, $competition, $category);
        $photo = $submission->photos()->firstOrFail();
        $criterion = $category->evaluationCriteria()->firstOrFail();
        $secondCategory = $this->category($competition);
        [, $secondSubmission] = $this->submittedEntry($secondUser, $competition, $secondCategory);
        $secondPhoto = $secondSubmission->photos()->firstOrFail();
        $secondCriterion = $secondCategory->evaluationCriteria()->firstOrFail();
        $award = $category->awards()->create([
            'award_reference_id' => AwardReference::where('code', 'first-prize')->value('id'),
            'quantity' => 1,
            'sort_order' => 10,
        ]);
        $juror = Juri::factory()->create();
        $category->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $secondCategory->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $this->openEvaluation($competition);
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer, 'eys')
            ->post(route('eys.competitions.publish-results', $competition))
            ->assertSessionHasErrors('results');

        $this->actingAs($juror, 'juri')->put(route('juri.evaluations.finalize', [$competition, $category]), [
            'scores' => [$photo->id => [$criterion->id => 7]],
        ])->assertRedirect();
        $this->actingAs($juror, 'juri')->put(route('juri.evaluations.finalize', [$competition, $secondCategory]), [
            'scores' => [$secondPhoto->id => [$secondCriterion->id => 9]],
        ])->assertRedirect();

        $this->actingAs($reviewer, 'eys')
            ->post(route('eys.competitions.publish-results', $competition))
            ->assertSessionHasErrors('results');

        $this->actingAs($reviewer, 'eys')
            ->post(route('eys.competitions.aggregate-results', $competition))
            ->assertRedirect();

        $result = $competition->evaluationRounds()->firstOrFail()->results()->where('submission_photo_id', $photo->id)->firstOrFail();
        $secondResult = $competition->evaluationRounds()->firstOrFail()->results()->where('submission_photo_id', $secondPhoto->id)->firstOrFail();
        $this->assertSame(1, $result->rank);
        $this->assertSame(1, $secondResult->rank);

        $this->actingAs($reviewer, 'eys')->put(route('eys.competitions.save-result-awards', $competition), [
            'award_assignments' => [$award->id => [1 => $result->id]],
        ])->assertRedirect();

        Notification::fake();
        $this->actingAs($reviewer, 'eys')
            ->post(route('eys.competitions.publish-results', $competition))
            ->assertRedirect();

        $this->assertNotNull($competition->refresh()->results_published_at);
        $this->assertDatabaseHas('competition_photo_results', [
            'submission_photo_id' => $photo->id,
            'total_score' => 7,
            'rank' => 1,
        ]);
        $this->assertDatabaseHas('competition_result_awards', [
            'competition_photo_result_id' => $result->id,
            'competition_category_award_id' => $award->id,
            'slot_number' => 1,
        ]);
        Notification::assertSentTo($user, MemberResultsPublishedNotification::class);
        Notification::assertSentTo($secondUser, MemberResultsPublishedNotification::class);
        Notification::assertSentTo($juror, JuryResultsPublishedNotification::class);

        $this->actingAs($user)->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertSee(__('uye.competitions.results'))
            ->assertSee('#1')
            ->assertSee('First Prize');
    }

    public function test_member_can_revise_photos_during_unfinalized_first_round_and_sees_anonymous_scorecard(): void
    {
        $user = $this->member();
        $competition = $this->competition();
        $category = $this->category($competition);
        [$entry, $submission] = $this->submittedEntry($user, $competition, $category);
        $original = $submission->photos()->firstOrFail();
        $juror = Juri::factory()->create(['first_name' => 'Gizli', 'last_name' => 'Jüri']);
        $category->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $competition->forceFill([
            'application_ends_at' => now()->subDay(),
            'competition_ends_at' => now()->addDay(),
            'evaluation_starts_at' => now()->subHour(),
            'evaluation_ends_at' => now()->addDays(2),
        ])->save();

        $source = Photo::factory()->for($user)->create(['disk_path' => 'portfolio/revision.jpg', 'thumb_path' => null]);
        Storage::disk('public')->put($source->disk_path, $this->fixture().'revision');
        $this->actingAs($user)->post(route('competitions.submission.portfolio.store', $submission), ['photo_id' => $source->id])->assertRedirect();
        $replacement = $submission->photos()->whereNull('withdrawn_at')->whereKeyNot($original->id)->firstOrFail();
        $this->actingAs($user)->delete(route('competitions.submission.photos.destroy', $original))->assertRedirect();
        $this->assertNotNull($original->refresh()->withdrawn_at);
        $this->assertDatabaseHas('competition_entry_events', ['competition_entry_id' => $entry->id, 'event' => 'photo_withdrawn_during_evaluation']);

        $criterion = $category->evaluationCriteria()->firstOrFail();
        $this->actingAs($juror, 'juri')->put(route('juri.evaluations.finalize', [$competition, $category]), [
            'scores' => [$replacement->id => [$criterion->id => 8]],
        ])->assertRedirect();

        $this->actingAs($user)->get(route('competitions.entry.show', $entry))
            ->assertOk()
            ->assertSee(__('uye.competitions.scorecard_title', ['round' => 1]))
            ->assertSee(__('uye.competitions.scorecard_evaluation', ['number' => 1]))
            ->assertDontSee('Gizli Jüri');

        $thirdSource = Photo::factory()->for($user)->create(['disk_path' => 'portfolio/second-revision.jpg', 'thumb_path' => null]);
        Storage::disk('public')->put($thirdSource->disk_path, $this->fixture().'second-revision');
        $this->actingAs($user)->post(route('competitions.submission.portfolio.store', $submission), ['photo_id' => $thirdSource->id])
            ->assertRedirect();
        $this->assertDatabaseCount('jury_evaluation_submissions', 0);
        $this->assertDatabaseHas('jury_scores', ['submission_photo_id' => $replacement->id, 'submitted_at' => null]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $juror->id, 'notifiable_type' => Juri::class]);
    }

    public function test_eys_can_create_committee_final_round_and_publish_it_on_result_subdomain(): void
    {
        $user = $this->member();
        $competition = $this->competition();
        $category = $this->category($competition);
        [, $submission] = $this->submittedEntry($user, $competition, $category);
        $photo = $submission->photos()->firstOrFail();
        $criterion = $category->evaluationCriteria()->firstOrFail();
        $award = $category->awards()->create([
            'award_reference_id' => AwardReference::where('code', 'first-prize')->value('id'),
            'quantity' => 1,
            'sort_order' => 10,
        ]);
        $juror = Juri::factory()->create();
        $category->jurorAssignments()->create(['juror_id' => $juror->id, 'sort_order' => 10]);
        $this->openEvaluation($competition);
        $this->actingAs($juror, 'juri')->put(route('juri.evaluations.finalize', [$competition, $category]), [
            'scores' => [$photo->id => [$criterion->id => 7]],
        ])->assertRedirect();

        $reviewer = $this->reviewer();
        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.aggregate-results', $competition))->assertRedirect();
        $firstResult = $competition->evaluationRounds()->firstOrFail()->results()->firstOrFail();
        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.create-final-round', $competition), [
            'photo_result_ids' => [$firstResult->id],
        ])->assertRedirect();
        $finalRound = $competition->evaluationRounds()->where('is_final', true)->firstOrFail();
        $decision = $finalRound->committeeDecisions()->firstOrFail();
        $session = $finalRound->jurySession()->with('attendances')->firstOrFail();
        $this->actingAs($reviewer, 'eys')->put(route('eys.competitions.jury-session.update', $competition), [
            'quorum' => 1,
            'attendances' => [$session->attendances->firstOrFail()->id => 'present'],
            'action' => 'open',
        ])->assertRedirect();
        $this->actingAs($reviewer, 'eys')->put(route('eys.competitions.save-final-round', $competition), [
            'decisions' => [$decision->id => ['decision' => 'selected', 'score' => 8, 'rank' => 1, 'note' => 'Kurul ortak kararı']],
        ])->assertRedirect();
        $finalResult = $finalRound->results()->firstOrFail();
        $this->assertSame(8, (int) $finalResult->average_score);

        $this->actingAs($reviewer, 'eys')->put(route('eys.competitions.jury-session.update', $competition), [
            'quorum' => 1,
            'minutes' => 'Kurul ortak değerlendirmesini tamamladı.',
            'attendances' => [$session->attendances->firstOrFail()->id => 'present'],
            'action' => 'close',
        ])->assertRedirect();

        $this->actingAs($reviewer, 'eys')->put(route('eys.competitions.save-result-awards', $competition), [
            'award_assignments' => [$award->id => [1 => $finalResult->id]],
        ])->assertRedirect();
        $this->actingAs($reviewer, 'eys')->get(route('eys.competitions.preview-results', $competition))
            ->assertOk()
            ->assertSee(__('result.preview_title'))
            ->assertSee(trim($user->first_name.' '.$user->last_name));
        $this->actingAs($reviewer, 'eys')
            ->get(route('eys.competitions.results.photos.show', [$competition, $photo]))
            ->assertOk();
        Notification::fake();
        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publish-results', $competition), [
            'publication_note' => 'Kurul tarafından onaylanan ilk sonuç yayını.',
        ])->assertRedirect();

        $publication = $competition->resultPublications()->firstOrFail();
        $this->assertSame(1, $publication->version);
        $this->assertSame($competition->id, $publication->snapshot['competition']['id']);
        $this->assertCount(1, $publication->snapshot['results']);
        $this->assertNotNull($publication->notified_at);

        $this->get(route('result.index'))->assertOk()->assertSee($competition->name);
        $this->get(route('result.competitions.show', $competition))
            ->assertOk()
            ->assertSee(trim($user->first_name.' '.$user->last_name))
            ->assertSee('First Prize');
        $this->get(route('result.photos.show', $photo))->assertOk();
    }

    private function competition(array $attributes = []): Competition
    {
        return Competition::factory()->create(array_merge([
            'audience' => CompetitionAudience::International,
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subDay(),
            'application_starts_at' => now()->subDay(),
            'application_ends_at' => now()->addDay(),
            'competition_ends_at' => now()->addDays(2),
            'evaluation_starts_at' => now()->addDays(3),
            'evaluation_ends_at' => now()->addDays(4),
        ], $attributes));
    }

    private function category(Competition $competition): CompetitionCategory
    {
        $category = $competition->categories()->create([
            'sort_order' => 10,
            'max_photos_per_participant' => 4,
            'age_eligibility_rule_id' => AgeEligibilityRule::where('code', 'no-age-check')->value('id'),
        ]);
        $category->upsertTranslations([
            'tr' => ['name' => 'Genel'],
            'en' => ['name' => 'Open'],
        ]);
        $category->genders()->sync([ParticipantGender::where('code', 'no-check')->value('id')]);
        $category->memberGroups()->sync([MemberGroup::where('code', 'no-membership-check')->value('id')]);
        $category->captureDevices()->sync([CaptureDevice::where('code', 'no-device-check')->value('id')]);
        $category->processingMethods()->sync([ProcessingMethod::where('code', 'no-processing-check')->value('id')]);
        $criterion = EvaluationCriterion::where('code', 'general-evaluation')->firstOrFail();
        $category->evaluationCriteria()->create([
            'evaluation_criterion_id' => $criterion->id,
            'min_score' => 3,
            'max_score' => 9,
            'weight' => 1,
            'sort_order' => 10,
        ]);

        return $category->fresh();
    }

    private function member(): User
    {
        return User::factory()->create([
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'uye_turu' => 3,
        ]);
    }

    private function startEntry(User $user, Competition $competition, CompetitionCategory $category): CompetitionEntry
    {
        $this->actingAs($user)->post(route('competitions.start', $competition))->assertRedirect();
        $entry = CompetitionEntry::whereBelongsTo($user)->whereBelongsTo($competition)->firstOrFail();
        $this->actingAs($user)->post(route('competitions.entry.categories.store', $entry), [
            'category_id' => $category->id,
        ])->assertRedirect();

        return $entry->fresh('submissions');
    }

    /** @return array{CompetitionEntry, CompetitionSubmission} */
    private function submittedEntry(User $user, Competition $competition, CompetitionCategory $category): array
    {
        $entry = $this->startEntry($user, $competition, $category);
        $submission = $entry->submissions()->firstOrFail();
        $source = Photo::factory()->for($user)->create([
            'disk_path' => 'portfolio/'.fake()->uuid().'.jpg',
            'thumb_path' => null,
        ]);
        Storage::disk('public')->put($source->disk_path, $this->fixture());
        $this->actingAs($user)->post(route('competitions.submission.portfolio.store', $submission), [
            'photo_id' => $source->id,
        ])->assertRedirect();
        $this->actingAs($user)->post(route('competitions.entry.submit', $entry), ['consent' => '1'])
            ->assertRedirect();

        return [$entry->fresh(), $submission->fresh()];
    }

    private function openEvaluation(Competition $competition): void
    {
        $competition->forceFill([
            'application_ends_at' => now()->subDays(3),
            'competition_ends_at' => now()->subDays(2),
            'evaluation_starts_at' => now()->subDay(),
            'evaluation_ends_at' => now()->addDay(),
        ])->save();
    }

    private function reviewer(): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('institution.competitions.manage');

        return $user;
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/photo-without-exif.jpg'));
    }
}
