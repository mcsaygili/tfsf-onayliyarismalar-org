<?php

namespace Tests\Feature\Temsilci;

use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilgiler_eksikse_uyari_gosterilir(): void
    {
        $temsilci = Temsilci::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.dashboard'));

        $response->assertSee(__('temsilci.dashboard.incomplete_title'));
    }

    public function test_bilgiler_tamamsa_uyari_gosterilmez(): void
    {
        $temsilci = Temsilci::factory()->create(['first_name' => 'Ahmet', 'last_name' => 'Demir']);

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.dashboard'));

        $response->assertDontSee(__('temsilci.dashboard.incomplete_title'));
    }
}
