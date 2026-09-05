<?php

namespace Tests\Feature;

use App\Models\CompetitionRegistration;
use App\Models\CompetitionRegistrationEvent;
use App\Models\CompetitionStatusLog;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use App\Models\Temsilci;
use App\Models\User;
use App\Services\CompetitionRegistrationService;
use App\Services\MemberEligibilityService;
use App\Services\RegistrationDocumentScanService;
use App\Services\RegistrationExceptionService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\CreatesRegistrationException;
use Tests\Support\PassingDocumentScanner;
use Tests\TestCase;

class RegistrationExceptionTest extends TestCase
{
    use CreatesRegistrationException, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_generic_competition_permission_cannot_grant_exceptions_or_view_recipient_details(): void
    {
        $f = $this->registrationFixture();
        $manager = $this->exceptionManager(false);
        $this->actingAs($manager, 'eys')->get(route('eys.competitions.registration-permissions', $f['competition']))->assertForbidden();
        $this->post(route('eys.competitions.registration-permissions.store', $f['competition']), ['actor_type' => 'institution', 'actor_id' => $f['staff']->id, 'version' => 0, 'active' => 1, 'reason' => 'Attempt without special permission.'])->assertForbidden();
        $this->assertDatabaseCount('registration_exception_grants', 0);
    }

    public function test_eys_can_grant_and_revoke_scoped_permission_with_audit_and_stale_form_protection(): void
    {
        $f = $this->registrationFixture();
        $this->actingAs($this->exceptionManager(), 'eys');
        $url = route('eys.competitions.registration-permissions.store', $f['competition']);
        $payload = ['actor_type' => 'institution', 'actor_id' => $f['staff']->id, 'version' => 0, 'active' => 1, 'reason' => 'Confirmed reviewer assignment.'];
        $this->post($url, array_replace($payload, ['reason' => '   ']))->assertSessionHasErrors();
        $this->post($url, array_replace($payload, ['actor_id' => InstitutionStaff::factory()->create()->id]))->assertNotFound();
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $grant = RegistrationExceptionGrant::sole();
        $this->assertTrue($grant->active);
        $this->get(route('eys.competitions.registration-permissions', $f['competition']))->assertOk()->assertSee($f['staff']->email);
        $this->post($url, array_replace($payload, ['active' => 0]))->assertSessionHasErrors('registration');
        $this->post($url, array_replace($payload, ['active' => 0, 'version' => 1]))->assertSessionHasNoErrors();
        $this->assertFalse($grant->fresh()->active);
        $this->assertSame(2, $grant->fresh()->version);
        $this->assertSame(2, CompetitionStatusLog::where('action', 'registration_exception_permission')->count());
    }

    public function test_regular_reviewer_cannot_lookup_members_or_approve_directly_without_a_grant(): void
    {
        $f = $this->exceptionFixture();
        $staff = InstitutionStaff::factory()->create(['institution_id' => $f['competition']->institution_id]);
        $this->actingAs($staff, 'institution')->get(route('institution.registrations.direct.create', $f['competition']))->assertNotFound();
        $this->post(route('institution.registrations.direct.lookup', $f['competition']), ['email' => $f['member']->email])->assertNotFound()->assertDontSee($f['member']->email);
        $this->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f))->assertNotFound();
        $this->assertSame('draft', $f['registration']->fresh()->status);
    }

    public function test_lookup_is_exact_authorized_and_does_not_create_a_registration(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution')->get(route('institution.registrations.index'))->assertOk()->assertSee(route('institution.registrations.direct.create', $f['competition']));
        $url = route('institution.registrations.direct.lookup', $f['competition']);
        $this->post($url, ['email' => 'missing@example.test'])->assertSessionHasErrors('email');
        $this->post($url, ['email' => $f['member']->email])->assertOk()->assertSee($f['member']->email)->assertSee('name="version" value="1"', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('competition_registrations', 1);
    }

    public function test_direct_creation_allocates_number_and_audits_staff_without_submitting_member_declarations(): void
    {
        $f = $this->exceptionFixture();
        $member = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f, ['user_id' => $member->id, 'version' => 0]))->assertSessionHasNoErrors();
        $registration = CompetitionRegistration::where('user_id', $member->id)->sole();
        $this->assertSame(2, $registration->number);
        $this->assertSame(2, $registration->version);
        $this->assertSame('approved', $registration->status);
        $this->assertSame('direct', $registration->approval_source);
        $this->assertTrue($registration->documents_waived);
        $this->assertSame(1, $registration->document_min);
        $this->assertSame($f['staff']->id, $registration->reviewed_by_id);
        $this->assertSame($f['grant']->id, $registration->exception_grant_id);
        $event = $registration->events()->where('event', 'exception_approved')->sole();
        $this->assertSame(1, $event->context['grant_version']);
        $this->assertSame(InstitutionStaff::class, $event->actor_type);
        $this->assertSame(1, $registration->events()->where('event', 'direct_registered')->count());
        $this->assertDatabaseMissing('competition_entries', ['user_id' => $member->id]);
        $this->actingAs($member)->get(route('competitions.registration.show', $f['competition']))->assertOk()->assertSee(__('registration.exception_waived'))->assertSee(__('registration.exception_member_requirement'))->assertDontSee(__('registration.requirement', ['min' => 1]));
    }

    public function test_document_minimum_needs_explicit_waiver_and_reason(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution');
        $url = route('institution.registrations.direct.store', $f['competition']);
        $this->post($url, $this->exceptionPayload($f, ['reason' => '   ']))->assertSessionHasErrors('reason');
        $this->post($url, $this->exceptionPayload($f, ['waive_documents' => 0]))->assertSessionHasErrors('registration');
        $this->assertSame('draft', $f['registration']->fresh()->status);
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasNoErrors();
        $this->assertTrue(app(MemberEligibilityService::class)->forCompetition($f['competition'], $f['member'])['eligible']);
    }

    public function test_waiver_cannot_bypass_quarantine_or_tampered_document_and_trusted_document_needs_no_waiver(): void
    {
        $f = $this->exceptionFixture();
        $document = app(CompetitionRegistrationService::class)->upload($f['registration'], $f['member'], 1, 1, $this->registrationPdf());
        $this->actingAs($f['staff'], 'institution');
        $url = route('institution.registrations.direct.store', $f['competition']);
        foreach (['pending', 'rejected', 'error'] as $state) {
            $document->update(['scan_status' => $state]);
            $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        }
        $document->update(['scan_status' => 'pending']);
        $this->app->instance(PdfDocumentScanner::class, new PassingDocumentScanner);
        app(RegistrationDocumentScanService::class)->scan($document->id);
        $bytes = Storage::disk('local')->get($document->disk_path);
        Storage::disk('local')->put($document->disk_path, 'tampered');
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        Storage::disk('local')->put($document->disk_path, $bytes);
        $this->post($url, $this->exceptionPayload($f, ['waive_documents' => 0]))->assertSessionHasNoErrors();
        $this->assertFalse($f['registration']->fresh()->documents_waived);
    }

    public function test_stale_grant_and_registration_versions_fail_and_permission_revocation_does_not_revoke_past_approval(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution');
        $url = route('institution.registrations.direct.store', $f['competition']);
        $this->post($url, $this->exceptionPayload($f, ['version' => 0]))->assertSessionHasErrors('registration');
        $this->post($url, $this->exceptionPayload($f, ['grant_version' => 2]))->assertSessionHasErrors('registration');
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasNoErrors();
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        app(RegistrationExceptionService::class)->setGrant($f['competition'], $f['manager'], $f['staff'], 1, false, 'Reviewer permission ended.');
        $this->post($url, $this->exceptionPayload($f))->assertNotFound();
        $this->assertTrue(app(CompetitionRegistrationService::class)->isApproved($f['competition'], $f['member']->id));
        $this->assertSame(1, $f['registration']->events()->where('event', 'exception_approved')->count());
    }

    public function test_rejected_registration_can_be_reconsidered_only_with_explicit_exception(): void
    {
        $f = $this->exceptionFixture(0);
        $service = app(CompetitionRegistrationService::class);
        $service->submit($f['registration'], $f['member'], 1);
        $service->decide($f['registration'], $f['staff'], 2, 'rejected', 'Earlier evidence insufficient.');
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f, ['waive_documents' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('rejected', $f['registration']->events()->where('event', 'exception_approved')->sole()->context['previous_status']);
        $this->assertSame(1, $f['registration']->events()->where('event', 'rejected')->count());
    }

    public function test_correction_clears_exception_and_regular_resubmission_requires_documents_again(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f))->assertSessionHasNoErrors();
        app(CompetitionRegistrationService::class)->decide($f['registration'], $f['staff'], 2, 'changes_requested', 'Recheck attendance evidence.');
        $registration = $f['registration']->fresh();
        $this->assertFalse($registration->documents_waived);
        $this->assertSame('normal', $registration->approval_source);
        $this->assertNull($registration->exception_grant_id);
        $this->actingAs($f['member'])->post(route('competitions.registration.submit', $registration), ['version' => 3])->assertSessionHasErrors('registration');
        $this->assertSame(1, $registration->events()->where('event', 'exception_approved')->count());
    }

    public function test_ineligible_member_and_closed_or_final_competition_cannot_be_overridden(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution');
        $url = route('institution.registrations.direct.store', $f['competition']);
        $f['member']->update(['status' => 0]);
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        $f['member']->update(['status' => 1]);
        $f['member']->forceFill(['email_verified_at' => null])->save();
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        $f['member']->forceFill(['email_verified_at' => now()])->save();
        $f['competition']->update(['application_ends_at' => now()->subMinute()]);
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        $f['competition']->forceFill(['application_ends_at' => now()->addDay(), 'results_published_at' => now()])->save();
        $this->post($url, $this->exceptionPayload($f))->assertSessionHasErrors('registration');
        $this->assertSame('draft', $f['registration']->fresh()->status);
    }

    public function test_representative_requires_own_grant_and_current_assignment(): void
    {
        $f = $this->exceptionFixture();
        $representative = Temsilci::factory()->create();
        $f['competition']->forceFill(['registration_reviewer' => 'representative', 'representative_id' => $representative->id])->save();
        $f['registration']->update(['reviewer' => 'representative']);
        $grant = app(RegistrationExceptionService::class)->setGrant($f['competition'], $f['manager'], $representative, 0, true, 'Assigned representative exception permission.');
        $this->actingAs($f['staff'], 'institution')->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f))->assertNotFound();
        $this->actingAs($representative, 'temsilci')->post(route('temsilci.registrations.direct.lookup', $f['competition']), ['email' => $f['member']->email])->assertOk();
        $f['competition']->forceFill(['representative_id' => Temsilci::factory()->create()->id])->save();
        $this->post(route('temsilci.registrations.direct.store', $f['competition']), $this->exceptionPayload($f, ['grant_version' => $grant->version]))->assertNotFound();
        $f['competition']->forceFill(['representative_id' => $representative->id])->save();
        $this->post(route('temsilci.registrations.direct.store', $f['competition']), $this->exceptionPayload($f, ['grant_version' => $grant->version]))->assertSessionHasNoErrors();
        $this->assertSame(Temsilci::class, $f['registration']->fresh()->reviewed_by_type);
    }

    public function test_validation_returns_to_get_form_with_member_and_reason_preserved(): void
    {
        $f = $this->exceptionFixture();
        $this->actingAs($f['staff'], 'institution');
        $this->from(route('institution.registrations.direct.lookup', $f['competition']))
            ->post(route('institution.registrations.direct.store', $f['competition']), $this->exceptionPayload($f, ['waive_documents' => 0]))
            ->assertRedirect(route('institution.registrations.direct.create', $f['competition']))->assertSessionHasErrors('registration');
        $this->get(route('institution.registrations.direct.create', $f['competition']))->assertOk()->assertSee($f['member']->email)->assertSee('Synthetic attendance evidence verified.');
    }

    public function test_inactive_staff_cannot_use_a_previously_granted_permission(): void
    {
        $f = $this->exceptionFixture();
        $f['staff']->update(['status' => false]);
        $this->expectException(NotFoundHttpException::class);
        app(CompetitionRegistrationService::class)->approveDirectly($f['competition'], $f['staff'], $f['member'], 1, 1, true, 'Attempt from stale staff object.');
    }

    public function test_grant_audit_failure_rolls_back_permission(): void
    {
        $f = $this->registrationFixture();
        $manager = $this->exceptionManager();
        CompetitionStatusLog::creating(fn () => throw new \RuntimeException('Injected grant audit failure'));
        try {
            app(RegistrationExceptionService::class)->setGrant($f['competition'], $manager, $f['staff'], 0, true, 'Synthetic permission reason.');
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected grant audit failure', $e->getMessage());
        }
        $this->assertDatabaseCount('registration_exception_grants', 0);
    }

    public function test_direct_approval_audit_failure_rolls_back_number_and_new_registration(): void
    {
        $f = $this->exceptionFixture();
        $member = User::factory()->create(['date_of_birth' => '1990-01-01', 'gender' => 'male']);
        CompetitionRegistrationEvent::creating(function ($event) {
            if ($event->event === 'exception_approved') {
                throw new \RuntimeException('Injected direct audit failure');
            }
        });
        try {
            app(CompetitionRegistrationService::class)->approveDirectly($f['competition'], $f['staff'], $member, 0, 1, true, 'Synthetic attendance verified.');
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected direct audit failure', $e->getMessage());
        }
        $this->assertSame(1, $f['competition']->fresh()->registration_sequence);
        $this->assertDatabaseCount('competition_registrations', 1);
        $this->assertDatabaseCount('competition_registration_events', 1);
    }
}
