<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\Country;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.countries.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.countries.manage');

        return $user;
    }

    public function test_ulke_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.countries.index'));

        $response->assertOk();
    }

    public function test_yeni_ulke_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.countries.store'), [
            'status' => '1',
            'iso_alpha2' => 'fr',
            'iso_alpha3' => 'fra',
            'tr' => ['official_name' => 'Fransa Cumhuriyeti', 'short_name' => 'Fransa', 'nationality' => 'Fransız'],
            'en' => ['official_name' => 'Republic of France', 'short_name' => 'France', 'nationality' => 'French'],
        ]);

        $response->assertRedirect(route('eys.countries.index'));

        $country = Country::query()->firstOrFail();

        $this->assertSame('FR', $country->iso_alpha2);
        $this->assertSame('FRA', $country->iso_alpha3);
        $this->assertSame('Fransa', $country->getTranslation('tr')?->short_name);
        $this->assertSame('France', $country->getTranslation('en')?->short_name);
    }

    public function test_varsayilan_dilde_resmi_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.countries.store'), [
            'status' => '1',
            'tr' => ['official_name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.official_name');
    }

    public function test_sehri_olan_ulke_silinemez(): void
    {
        $user = $this->admin();

        $country = Country::create(['status' => true]);
        $country->cities()->create(['status' => true]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.countries.destroy', $country));

        $response->assertRedirect(route('eys.countries.index'));
        $this->assertModelExists($country);
    }

    public function test_sehri_olmayan_ulke_silinebilir(): void
    {
        $user = $this->admin();

        $country = Country::create(['status' => true]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.countries.destroy', $country));

        $response->assertRedirect(route('eys.countries.index'));
        $this->assertSoftDeleted($country);
    }

    public function test_izinsiz_kullanici_ulkeler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.countries.index'));

        $response->assertForbidden();
    }
}
