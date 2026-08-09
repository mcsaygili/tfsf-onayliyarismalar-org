<?php

namespace Tests\Feature\Eys;

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
}
