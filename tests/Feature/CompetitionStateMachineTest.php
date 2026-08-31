<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Services\CompetitionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CompetitionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_gecerli_gecis_durumu_degistirir_ve_standart_audit_baglami_yazar(): void
    {
        $competition = Competition::factory()->create();
        request()->headers->set('X-Request-ID', 'req-state-machine-1');
        request()->headers->set('User-Agent', 'TFSF Test Client');

        app(CompetitionStateMachine::class)->transition(
            $competition,
            CompetitionStatus::Submitted,
            'submitted',
            $competition->institutionStaff,
            extra: ['submitted_at' => now()],
        );

        $this->assertSame(CompetitionStatus::Submitted, $competition->fresh()->status);
        $this->assertDatabaseHas('competition_status_logs', [
            'competition_id' => $competition->id,
            'action' => 'submitted',
            'from_status' => CompetitionStatus::Draft->value,
            'to_status' => CompetitionStatus::Submitted->value,
            'actor_guard' => 'institution',
            'request_id' => 'req-state-machine-1',
            'user_agent' => 'TFSF Test Client',
        ]);
    }

    public function test_gecersiz_gecis_reddedilir_ve_audit_yazilmaz(): void
    {
        $competition = Competition::factory()->create();

        try {
            app(CompetitionStateMachine::class)->transition(
                $competition,
                CompetitionStatus::Approved,
                'approved',
                $competition->institutionStaff,
            );
            $this->fail('Geçersiz durum geçişi reddedilmeliydi.');
        } catch (LogicException) {
            $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
            $this->assertDatabaseCount('competition_status_logs', 0);
        }
    }
}
