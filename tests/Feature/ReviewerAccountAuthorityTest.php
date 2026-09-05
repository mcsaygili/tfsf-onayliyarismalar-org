<?php

namespace Tests\Feature;

use App\Models\Temsilci;
use App\Policies\CompetitionSubmissionApprovalPolicy;
use App\Services\CompetitionRegistrationService;
use App\Services\SubmissionApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\CreatesCompetitionRegistration;
use Tests\TestCase;

class ReviewerAccountAuthorityTest extends TestCase
{
    use CreatesCompetitionRegistration, RefreshDatabase;

    private function reviewFixture(): array
    {
        Notification::fake();
        $f = $this->registrationFixture(0);
        $representative = Temsilci::factory()->create();
        $f['competition']->forceFill(['representative_id' => $representative->id])->save();
        $approval = $f['submission']->approvals()->create(['approval_type' => 'representative', 'status' => 'pending', 'sequence' => 1]);

        return $f + compact('representative', 'approval');
    }

    public function test_inactive_representative_cannot_decide_through_service(): void
    {
        $f = $this->reviewFixture();
        $f['representative']->update(['status' => false]);
        try {
            app(SubmissionApprovalService::class)->decide($f['approval'], $f['representative'], true);
            $this->fail('Inactive representative must not approve a submission.');
        } catch (NotFoundHttpException) {
            $this->assertSame('pending', $f['approval']->fresh()->status->value);
            Notification::assertNothingSent();
        }
    }

    public function test_reactivated_account_does_not_make_an_old_photo_decision_request_valid_again(): void
    {
        $f = $this->reviewFixture();
        $f['representative']->fresh()->update(['status' => false]);
        $f['representative']->fresh()->update(['status' => true]);
        try {
            app(SubmissionApprovalService::class)->decide($f['approval'], $f['representative'], true);
            $this->fail('Old authority must not revive.');
        } catch (NotFoundHttpException) {
            $this->assertSame('pending', $f['approval']->fresh()->status->value);
            $this->assertSame(0, $f['submission']->entry->events()->count());
            Notification::assertNothingSent();
        }
        app(SubmissionApprovalService::class)->decide($f['approval'], $f['representative']->fresh(), true);
        $this->assertSame('approved', $f['approval']->fresh()->status->value);
    }

    public function test_changed_institution_staff_identity_invalidates_in_flight_approval(): void
    {
        $f = $this->reviewFixture();
        $f['approval']->update(['approval_type' => 'institution']);
        $f['staff']->fresh()->update(['email' => 'changed-reviewer@example.test']);
        $this->expectException(NotFoundHttpException::class);
        app(SubmissionApprovalService::class)->decide($f['approval'], $f['staff'], false, 'Old account context.');
    }

    public function test_deleted_reviewer_is_denied_without_writing_a_decision(): void
    {
        $f = $this->reviewFixture();
        $f['representative']->delete();
        try {
            app(SubmissionApprovalService::class)->decide($f['approval'], $f['representative'], true);
            $this->fail('Deleted reviewer must be denied.');
        } catch (NotFoundHttpException) {
            $this->assertSame('pending', $f['approval']->fresh()->status->value);
        }
    }

    public function test_preregistration_decision_rejects_revoked_and_reactivated_reviewer_context(): void
    {
        $f = $this->reviewFixture();
        app(CompetitionRegistrationService::class)->submit($f['registration'], $f['member'], 1);
        $f['staff']->fresh()->update(['status' => false]);
        $f['staff']->fresh()->update(['status' => true]);
        try {
            app(CompetitionRegistrationService::class)->decide($f['registration'], $f['staff'], 2, 'approved', null);
            $this->fail('Old authority must not approve pre-registration.');
        } catch (NotFoundHttpException) {
            $this->assertSame('pending', $f['registration']->fresh()->status);
            $this->assertSame(2, $f['registration']->fresh()->version);
        }
    }

    public function test_unrelated_member_cannot_be_used_as_review_actor(): void
    {
        $f = $this->reviewFixture();
        $this->expectException(NotFoundHttpException::class);
        app(SubmissionApprovalService::class)->decide($f['approval'], $f['member'], true);
    }

    public function test_policy_itself_denies_inactive_representative(): void
    {
        $f = $this->reviewFixture();
        $f['representative']->update(['status' => false]);
        $response = app(CompetitionSubmissionApprovalPolicy::class)->decide($f['representative'], $f['approval']);
        $this->assertTrue($response->denied());
        $this->assertSame(404, $response->status());
    }
}
