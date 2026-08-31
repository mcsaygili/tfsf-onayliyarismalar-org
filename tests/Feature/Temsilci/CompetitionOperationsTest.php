<?php

namespace Tests\Feature\Temsilci;

use App\Models\Competition;
use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_temsilci_atandigi_yarismayi_gorur_ve_izleme_raporu_gonderebilir(): void
    {
        $representative = Temsilci::factory()->create();
        $competition = Competition::factory()->create();
        $competition->forceFill(['representative_id' => $representative->id])->save();

        $this->actingAs($representative, 'temsilci')->get(route('temsilci.competitions.index'))
            ->assertOk()
            ->assertSee($competition->name);
        $this->actingAs($representative, 'temsilci')->get(route('temsilci.competitions.show', $competition))
            ->assertOk();

        $this->actingAs($representative, 'temsilci')->post(route('temsilci.competitions.reports.store', $competition), [
            'status' => 'risk',
            'subject' => 'Katılımcı onaylarında gecikme',
            'note' => 'Bekleyen katılımcı onayları için kurumla iletişim kurulmalıdır.',
            'observed_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('competition_monitoring_reports', [
            'competition_id' => $competition->id,
            'representative_id' => $representative->id,
            'status' => 'risk',
        ]);
    }

    public function test_temsilci_atanmadigi_yarismaya_erissemez(): void
    {
        $representative = Temsilci::factory()->create();
        $competition = Competition::factory()->create();

        $this->actingAs($representative, 'temsilci')
            ->get(route('temsilci.competitions.show', $competition))
            ->assertNotFound();
    }
}
