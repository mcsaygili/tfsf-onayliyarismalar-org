<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\City;
use App\Models\Country;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.cities.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.cities.manage');

        return $user;
    }

    public function test_sehir_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.cities.index'));

        $response->assertOk();
    }

    public function test_yeni_sehir_olusturulabilir_ve_bir_ulkeye_baglanir(): void
    {
        $user = $this->admin();
        $country = Country::create(['status' => true]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.cities.store'), [
            'country_id' => $country->id,
            'status' => '1',
            'tr' => ['official_name' => 'Ankara'],
            'en' => ['official_name' => 'Ankara'],
        ]);

        $response->assertRedirect(route('eys.cities.index'));

        $city = City::query()->firstOrFail();

        $this->assertSame($country->id, $city->country_id);
        $this->assertSame('Ankara', $city->getTranslation('tr')?->official_name);
    }

    public function test_gecersiz_ulkeyle_sehir_olusturulamaz(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.cities.store'), [
            'country_id' => (string) Str::uuid(),
            'status' => '1',
            'tr' => ['official_name' => 'Ankara'],
        ]);

        $response->assertSessionHasErrors('country_id');
    }

    public function test_ulkenin_aktif_sehirleri_json_olarak_donebilir(): void
    {
        $user = $this->admin();
        $country = Country::create(['status' => true]);
        $city = $country->cities()->create(['status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'İzmir']]);
        $inactive = $country->cities()->create(['status' => false]);
        $inactive->upsertTranslations(['tr' => ['official_name' => 'Pasif Şehir']]);

        $response = $this->actingAs($user, 'eys')->getJson(route('eys.cities.by-country', $country));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $city->id]);
    }

    public function test_izinsiz_kullanici_sehirler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.cities.index'));

        $response->assertForbidden();
    }
}
