<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionStatusLog;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use App\Services\CompetitionRegistrationService;
use App\Services\InstitutionCompetitionAccess;
use App\Services\PanelAccountAccess;
use App\Services\RegistrationDocumentScanService;
use App\Services\RegistrationExceptionService;
use App\Services\SecretariatService;
use App\Services\SubmissionApprovalService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesSecretariat;
use Tests\Support\PassingDocumentScanner;
use Tests\TestCase;

class SecretariatTest extends TestCase
{
    use CreatesSecretariat, RefreshDatabase;

    public function test_eys_creates_independent_unverified_account_with_no_institution_and_audit(): void
    {
        $f = $this->secretariatFixture();
        $this->actingAs($f['manager'], 'eys')->post(route('eys.secretariats.store'), ['email' => 'new-secretariat@example.test', 'first_name' => 'Test', 'last_name' => 'Secretary', 'password' => 'SecureTest123!', 'password_confirmation' => 'SecureTest123!', 'status' => 1, 'institution_id' => $f['competition']->institution_id])->assertSessionHasNoErrors();
        $account = InstitutionStaff::where('email', 'new-secretariat@example.test')->sole();
        $this->assertTrue($account->isSecretariat());
        $this->assertNull($account->institution_id);
        $this->assertNull($account->email_verified_at);
        $this->assertDatabaseHas('secretariat_account_events', ['account_id' => $account->id, 'action' => 'created', 'actor_id' => $f['manager']->id]);
        $this->assertStringNotContainsString('SecureTest123!', DB::table('secretariat_account_events')->where('account_id', $account->id)->value('changes'));
    }

    public function test_generic_competition_manager_cannot_manage_secretariat_accounts_or_assignments(): void
    {
        $f = $this->secretariatFixture();
        $this->actingAs($this->exceptionManager(), 'eys')->get(route('eys.secretariats.index'))->assertForbidden();
        $this->get(route('eys.competitions.secretariat', $f['competition']))->assertForbidden();
        $this->post(route('eys.competitions.secretariat.store', $f['competition']), ['account_id' => null, 'version' => 1, 'reason' => 'Unauthorized assignment removal.'])->assertForbidden();
    }

    public function test_secretariat_logs_in_without_institution_and_sees_only_assigned_competitions(): void
    {
        $f = $this->secretariatFixture();
        $other = Competition::factory()->create();
        $this->assertNull(app(PanelAccountAccess::class)->denialReason($f['secretariat']));
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.dashboard'))->assertOk()->assertViewHas('competitions', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $f['competition']->id);
        $this->assertFalse(app(InstitutionCompetitionAccess::class)->allows($other, $f['secretariat']));
        $this->get(route('institution.secretariat.profile'))->assertOk();
        $this->get(route('institution.password.edit'))->assertOk();
    }

    public function test_secretariat_cannot_manage_institution_staff_competition_wizard_or_promote_itself(): void
    {
        $f = $this->secretariatFixture();
        $this->actingAs($f['secretariat'], 'institution');
        foreach (['institution.profile.edit', 'institution.staff.index', 'institution.staff.create', 'institution.competitions.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->patch(route('institution.staff.update', $f['staff']), ['account_kind' => 'institution', 'status' => 0])->assertForbidden();
        $this->post(route('institution.competitions.store'))->assertForbidden();
        $this->assertTrue($f['staff']->fresh()->status);
    }

    public function test_secretariat_reviews_assigned_preregistration_and_loses_access_immediately_on_reassignment(): void
    {
        $f = $this->secretariatFixture();
        app(CompetitionRegistrationService::class)->submit($f['registration'], $f['member'], 1);
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.registrations.index'))->assertOk()->assertSee($f['member']->first_name);
        $this->get(route('institution.registrations.show', $f['registration']))->assertOk();
        $other = InstitutionStaff::factory()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], $other->id, 1, 'Replace assigned secretary.');
        $this->post(route('institution.registrations.decide', $f['registration']), ['version' => 2, 'decision' => 'approved'])->assertNotFound();
        $this->get(route('institution.registrations.index'))->assertDontSee($f['member']->first_name);
        $this->actingAs($other, 'institution')->post(route('institution.registrations.decide', $f['registration']), ['version' => 2, 'decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame($other->id, $f['registration']->fresh()->reviewed_by_id);
    }

    public function test_one_secretariat_can_operate_competitions_of_different_institutions(): void
    {
        $f = $this->secretariatFixture();
        $other = $this->registrationFixture(0);
        app(SecretariatService::class)->assign($other['competition'], $f['manager'], $f['secretariat']->id, 0, 'Second institution assignment.');
        $this->assertNotSame($f['competition']->institution_id, $other['competition']->institution_id);
        $this->assertTrue(app(InstitutionCompetitionAccess::class)->allows($other['competition'], $f['secretariat']));
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.dashboard'))->assertViewHas('competitions', fn ($rows) => $rows->count() === 2);
        $f['competition']->institution->update(['status' => false]);
        $this->assertFalse(app(InstitutionCompetitionAccess::class)->allows($f['competition'], $f['secretariat']));
        $this->assertTrue(app(InstitutionCompetitionAccess::class)->allows($other['competition'], $f['secretariat']));
    }

    public function test_reassignment_revokes_old_direct_approval_grant_and_does_not_transfer_it(): void
    {
        $f = $this->secretariatFixture();
        $grant = app(RegistrationExceptionService::class)->setGrant($f['competition']->fresh(), $f['manager'], $f['secretariat'], 0, true, 'Explicit direct approval permission.');
        $other = InstitutionStaff::factory()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], $other->id, 1, 'Secretary replacement reason.');
        $this->assertFalse($grant->fresh()->active);
        $this->assertSame(2, $grant->fresh()->version);
        $this->assertFalse(RegistrationExceptionGrant::where('actor_id', $other->id)->exists());
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], $f['secretariat']->id, 2, 'Original secretary reassigned.');
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.registrations.direct.create', $f['competition']))->assertNotFound();
    }

    public function test_invalid_or_unverified_assignment_and_stale_form_are_rejected(): void
    {
        $f = $this->secretariatFixture();
        $this->actingAs($f['manager'], 'eys');
        $url = route('eys.competitions.secretariat.store', $f['competition']);
        $base = ['account_id' => $f['staff']->id, 'version' => 1, 'reason' => 'Assignment validation test.'];
        $this->post($url, $base)->assertUnprocessable();
        $unverified = InstitutionStaff::factory()->unverified()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        $this->post($url, array_replace($base, ['account_id' => $unverified->id]))->assertUnprocessable();
        $this->post($url, array_replace($base, ['account_id' => null, 'version' => 0]))->assertSessionHasErrors('secretariat');
        $this->assertSame($f['secretariat']->id, $f['competition']->fresh()->secretariat_id);
    }

    public function test_account_update_uses_context_and_email_change_invalidates_verification_and_session_stamp(): void
    {
        $f = $this->secretariatFixture();
        $service = app(SecretariatService::class);
        $context = $service->context($f['secretariat']);
        $stamp = $f['secretariat']->security_stamp;
        $data = ['context' => $context, 'email' => 'changed-secretariat@example.test', 'first_name' => 'Changed', 'last_name' => 'Secretary', 'status' => 1];
        $this->actingAs($f['manager'], 'eys')->patch(route('eys.secretariats.update', $f['secretariat']), $data)->assertSessionHasNoErrors();
        $this->assertNull($f['secretariat']->fresh()->email_verified_at);
        $this->assertNotSame($stamp, $f['secretariat']->fresh()->security_stamp);
        $this->patch(route('eys.secretariats.update', $f['secretariat']), array_replace($data, ['status' => 0]))->assertSessionHasErrors('context');
        $this->assertTrue($f['secretariat']->fresh()->status);
    }

    public function test_secretariat_profile_cannot_change_account_type_status_or_email(): void
    {
        $f = $this->secretariatFixture();
        $this->actingAs($f['secretariat'], 'institution')->patch(route('institution.secretariat.profile.update'), ['context' => app(SecretariatService::class)->context($f['secretariat']), 'first_name' => 'Updated', 'last_name' => 'Secretary', 'phone' => '555', 'status' => 0, 'account_kind' => 'institution', 'email' => 'takeover@example.test'])->assertSessionHasNoErrors();
        $account = $f['secretariat']->fresh();
        $this->assertTrue($account->isSecretariat());
        $this->assertTrue($account->status);
        $this->assertSame($f['secretariat']->email, $account->email);
        $this->get(route('institution.dashboard'))->assertOk();
    }

    public function test_real_login_and_deactivation_use_existing_panel_session_protection(): void
    {
        $f = $this->secretariatFixture();
        $f['secretariat']->update(['password' => 'SecretariatTest123!']);
        $this->post(route('institution.login'), ['email' => $f['secretariat']->email, 'password' => 'SecretariatTest123!'])->assertSessionHasNoErrors();
        $this->get(route('institution.dashboard'))->assertOk();
        $f['secretariat']->update(['status' => false]);
        $this->get(route('institution.dashboard'))->assertRedirect(route('institution.login'));
        $this->assertGuest('institution');
    }

    public function test_secretariat_can_review_photos_but_service_rechecks_assignment_before_deciding(): void
    {
        Notification::fake();
        $f = $this->secretariatFixture();
        $approval = $f['submission']->approvals()->create(['approval_type' => 'institution', 'status' => 'pending', 'sequence' => 1]);
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.participant-submissions.index'))->assertOk()->assertSee($f['member']->first_name);
        $this->get(route('institution.participant-submissions.show', $approval))->assertOk();
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], null, 1, 'Assignment removed before decision.');
        try {
            app(SubmissionApprovalService::class)->decide($approval, $f['secretariat'], true);
            $this->fail('Stale assignment must fail');
        } catch (AuthorizationException) {
            $this->assertSame('pending', $approval->fresh()->status->value);
        }
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], $f['secretariat']->id, 2, 'Assignment restored for review.');
        $this->post(route('institution.participant-submissions.decide', $approval), ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $approval->fresh()->status->value);
    }

    public function test_assigned_secretariat_document_download_obeys_private_scope(): void
    {
        Storage::fake('local');
        $f = $this->secretariatFixture();
        $document = app(CompetitionRegistrationService::class)->upload($f['registration'], $f['member'], 1, 1, $this->registrationPdf());
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        app(RegistrationDocumentScanService::class)->scan($document->id);
        app(CompetitionRegistrationService::class)->submit($f['registration']->fresh(), $f['member'], 2);
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.registrations.documents.show', $document))->assertOk();
        $other = InstitutionStaff::factory()->create(['account_kind' => 'secretariat', 'institution_id' => null]);
        $this->actingAs($other, 'institution')->get(route('institution.registrations.documents.show', $document))->assertNotFound();
    }

    public function test_failed_assignment_audit_rolls_back_assignment_and_exception_revocation(): void
    {
        $f = $this->secretariatFixture();
        $grant = app(RegistrationExceptionService::class)->setGrant($f['competition']->fresh(), $f['manager'], $f['secretariat'], 0, true, 'Explicit direct permission.');
        CompetitionStatusLog::creating(fn () => throw new \RuntimeException('Injected assignment audit failure'));
        try {
            app(SecretariatService::class)->assign($f['competition'], $f['manager'], null, 1, 'Synthetic removal reason.');
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected assignment audit failure', $e->getMessage());
        }
        $this->assertSame($f['secretariat']->id, $f['competition']->fresh()->secretariat_id);
        $this->assertTrue($grant->fresh()->active);
    }
}
