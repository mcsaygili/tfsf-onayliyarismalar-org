<?php

namespace Tests\Feature\Eys;

use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Enums\Module;
use App\Models\Competition;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompetitionPublicationLifecycleTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\ReadsResultContext;

    public function test_eys_can_suspend_resume_unpublish_and_cancel_an_approved_competition_with_audit_history(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->create([
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subDay(),
            'publication_state' => CompetitionPublicationState::Published,
        ]);

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publication.update', [$competition, 'suspend']), [
            'reason' => 'Geçici operasyon kontrolü yapılıyor.',
        ])->assertRedirect();
        $this->assertSame(CompetitionPublicationState::Suspended, $competition->refresh()->publication_state);
        $this->assertFalse($competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists());

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publication.update', [$competition, 'resume']))->assertRedirect();
        $this->assertSame(CompetitionPublicationState::Published, $competition->refresh()->publication_state);
        $this->assertTrue($competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists());

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publication.update', [$competition, 'unpublish']), [
            'reason' => 'Kurum bilgileri yeniden incelenecek.',
        ])->assertRedirect();
        $this->assertSame(CompetitionPublicationState::Unpublished, $competition->refresh()->publication_state);

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publication.update', [$competition, 'cancel']), [
            'reason' => 'Yarışma düzenleme kararı geri çekildi.',
        ])->assertRedirect();
        $this->assertSame(CompetitionPublicationState::Cancelled, $competition->refresh()->publication_state);
        $this->assertDatabaseHas('competition_status_logs', ['competition_id' => $competition->id, 'action' => 'competition_suspended']);
        $this->assertDatabaseHas('competition_status_logs', ['competition_id' => $competition->id, 'action' => 'competition_cancelled']);

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.publication.update', [$competition, 'resume']))
            ->assertSessionHasErrors('publication');
    }

    public function test_published_results_can_be_withdrawn_for_correction_and_version_is_preserved(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->create([
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subDay(),
            'publication_state' => CompetitionPublicationState::Published,
            'results_published_at' => now()->subHour(),
            'results_publication_version' => 2,
        ]);

        $this->actingAs($reviewer, 'eys')->post(route('eys.competitions.unpublish-results', $competition), [
            'reason' => 'Ödül atamalarındaki yazım hatası düzeltilecek.',
        ])->assertRedirect();

        $this->assertNull($competition->refresh()->results_published_at);
        $this->assertSame(2, $competition->results_publication_version);
        $this->assertDatabaseHas('competition_status_logs', [
            'competition_id' => $competition->id,
            'action' => 'results_unpublished_for_correction',
        ]);
    }

    public function test_results_cannot_be_published_while_competition_is_not_public(): void
    {
        $reviewer = $this->reviewer();
        $competition = Competition::factory()->create([
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subDay(),
            'publication_state' => CompetitionPublicationState::Suspended,
        ]);

        $this->actingAs($reviewer, 'eys')
            ->post(route('eys.competitions.publish-results', $competition), ['result_context' => $this->resultContextFor($competition)])
            ->assertSessionHasErrors('results');

        $this->assertNull($competition->refresh()->results_published_at);
    }

    private function reviewer(): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Institution->value);
        Permission::firstOrCreate(['name' => 'institution.competitions.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('institution.competitions.manage');

        return $user;
    }
}
