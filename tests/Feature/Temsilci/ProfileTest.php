<?php

namespace Tests\Feature\Temsilci;

use App\Models\Temsilci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_sifre_islemleri_sayfasi_dogrulanmis_temsilci_icin_goruntulenebilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.password.edit'));

        $response->assertOk();
    }

    public function test_temsilci_sifresini_guncelleyebilir(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->from(route('temsilci.password.edit'))->put(route('temsilci.password.update'), [
            'current_password' => 'password',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertRedirect(route('temsilci.password.edit'));
        $this->assertTrue(Hash::check('yeni-guclu-sifre', $temsilci->fresh()->password));
    }

    public function test_yanlis_mevcut_sifreyle_temsilci_sifresi_guncellenemez(): void
    {
        $temsilci = Temsilci::factory()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->put(route('temsilci.password.update'), [
            'current_password' => 'yanlis-sifre',
            'password' => 'yeni-guclu-sifre',
            'password_confirmation' => 'yeni-guclu-sifre',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
    }

    public function test_dogrulanmamis_temsilci_sifre_sayfasina_erisemez(): void
    {
        $temsilci = Temsilci::factory()->unverified()->create();

        $response = $this->actingAs($temsilci, 'temsilci')->get(route('temsilci.password.edit'));

        $response->assertRedirect(route('temsilci.verification.notice'));
    }
}
