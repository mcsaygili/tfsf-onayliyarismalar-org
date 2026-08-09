<?php

namespace Tests\Feature\Temsilci;

use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_temsilci_bilgileri_sayfasi_dogrulanmis_temsilci_icin_goruntulenebilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.profile.edit'));

        $response->assertOk();
    }

    public function test_temsilci_bilgileri_guncellenebilir(): void
    {
        $temsilci = Temsilci::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($temsilci, 'temsilci')->patch(route('temsilci.profile.update'), [
            'first_name' => 'Ahmet',
            'last_name' => 'Demir',
            'email' => $temsilci->email,
            'phone' => '+90 555 000 00 00',
        ]);

        $response->assertRedirect(route('temsilci.profile.edit'));

        $this->assertSame('Ahmet', $temsilci->fresh()->first_name);
        $this->assertSame('Demir', $temsilci->fresh()->last_name);
    }

    public function test_ad_soyad_bos_birakilamaz(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->patch(route('temsilci.profile.update'), [
            'first_name' => '',
            'last_name' => '',
            'email' => $temsilci->email,
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_dogrulanmamis_temsilci_bilgileri_sayfasina_erisemez(): void
    {
        $temsilci = Temsilci::factory()->unverified()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.profile.edit'));

        $response->assertRedirect(route('temsilci.verification.notice'));
    }
}
