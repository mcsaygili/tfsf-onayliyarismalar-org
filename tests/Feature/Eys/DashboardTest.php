<?php

namespace Tests\Feature\Eys;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\EysUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_gosterge_paneli_toplam_kullanici_sayisini_gosterir(): void
    {
        $user = EysUser::factory()->create();
        EysUser::factory()->count(2)->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.dashboard'));

        $response->assertOk();
        $response->assertViewHas('userCount', 3);
        $response->assertSee('3');
    }

    public function test_bilgiler_eksikse_uyari_gosterilir(): void
    {
        $user = EysUser::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.dashboard'));

        $response->assertSee(__('eys.dashboard.incomplete_title'));
    }

    public function test_bilgiler_tamamsa_uyari_gosterilmez(): void
    {
        $user = EysUser::factory()->create(['first_name' => 'Ahmet', 'last_name' => 'Demir']);

        $response = $this->actingAs($user, 'eys')->get(route('eys.dashboard'));

        $response->assertDontSee(__('eys.dashboard.incomplete_title'));
    }

    public function test_operasyon_kuyrugu_durum_ve_tarih_araligina_gore_filtrelenebilir(): void
    {
        $user = EysUser::factory()->create();
        $matching = Competition::factory()->submitted()->create([
            'application_ends_at' => '2026-09-15 18:00:00',
        ]);
        $wrongStatus = Competition::factory()->needsInfo()->create([
            'application_ends_at' => '2026-09-15 18:00:00',
        ]);
        $wrongDate = Competition::factory()->submitted()->create([
            'application_ends_at' => '2026-10-15 18:00:00',
        ]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.dashboard', [
            'status' => CompetitionStatus::Submitted->value,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
        ]));

        $response->assertOk();
        $queue = $response->viewData('attentionQueue');
        $this->assertTrue($queue->contains($matching));
        $this->assertFalse($queue->contains($wrongStatus));
        $this->assertFalse($queue->contains($wrongDate));
    }

    public function test_geciken_operasyonlar_belirginlestirilir_ve_ayri_filtrelenebilir(): void
    {
        $user = EysUser::factory()->create();
        $overdue = Competition::factory()->submitted()->create([
            'submitted_at' => now()->subHours(60),
        ]);
        $fresh = Competition::factory()->submitted()->create([
            'submitted_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.dashboard', ['overdue' => 1]));

        $response->assertOk()->assertSee('saat gecikmiş');
        $queue = $response->viewData('attentionQueue');
        $this->assertTrue($queue->contains($overdue));
        $this->assertFalse($queue->contains($fresh));
        $this->assertTrue($queue->firstWhere('id', $overdue->id)->operation_overdue);
    }
}
