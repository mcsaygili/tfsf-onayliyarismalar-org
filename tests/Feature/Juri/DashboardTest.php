<?php

namespace Tests\Feature\Juri;

use App\Models\Juri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilgiler_eksikse_uyari_gosterilir(): void
    {
        $juri = Juri::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertSee(__('juri.dashboard.incomplete_title'));
    }

    public function test_bilgiler_tamamsa_uyari_gosterilmez(): void
    {
        $juri = Juri::factory()->create(['first_name' => 'Ayşe', 'last_name' => 'Kaya']);

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertDontSee(__('juri.dashboard.incomplete_title'));
    }
}
