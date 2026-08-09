<?php

namespace Tests\Feature\Juri;

use App\Models\Juri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_juri_bilgileri_sayfasi_dogrulanmis_juri_icin_goruntulenebilir(): void
    {
        $juri = Juri::factory()->create();

        $response = $this->actingAs($juri, 'juri')->get(route('juri.profile.edit'));

        $response->assertOk();
    }

    public function test_juri_bilgileri_guncellenebilir(): void
    {
        $juri = Juri::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($juri, 'juri')->patch(route('juri.profile.update'), [
            'first_name' => 'Ayşe',
            'last_name' => 'Kaya',
            'email' => $juri->email,
            'phone' => '+90 555 000 00 00',
            'tckimlikno' => '12345678901',
        ]);

        $response->assertRedirect(route('juri.profile.edit'));

        $this->assertSame('Ayşe', $juri->fresh()->first_name);
        $this->assertSame('12345678901', $juri->fresh()->tckimlikno);
    }

    public function test_ad_soyad_bos_birakilamaz(): void
    {
        $juri = Juri::factory()->create();

        $response = $this->actingAs($juri, 'juri')->patch(route('juri.profile.update'), [
            'first_name' => '',
            'last_name' => '',
            'email' => $juri->email,
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_dogrulanmamis_juri_bilgileri_sayfasina_erisemez(): void
    {
        $juri = Juri::factory()->unverified()->create();

        $response = $this->actingAs($juri, 'juri')->get(route('juri.profile.edit'));

        $response->assertRedirect(route('juri.verification.notice'));
    }
}
