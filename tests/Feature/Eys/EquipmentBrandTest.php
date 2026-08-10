<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EquipmentBrand;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EquipmentBrandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.equipment_brands.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.equipment_brands.manage');

        return $user;
    }

    public function test_marka_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-brands.index'));

        $response->assertOk();
    }

    public function test_yeni_marka_duz_metin_olarak_olusturulabilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-brands.store'), [
            'status' => '1',
            'name' => 'Canon',
        ]);

        $response->assertRedirect(route('eys.equipment-brands.index'));

        $equipmentBrand = EquipmentBrand::query()->firstOrFail();

        $this->assertSame('Canon', $equipmentBrand->name);
        $this->assertTrue($equipmentBrand->status);
    }

    public function test_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-brands.store'), [
            'status' => '1',
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_ayni_isimde_marka_tekrar_olusturulamaz(): void
    {
        $user = $this->admin();
        EquipmentBrand::create(['name' => 'Canon', 'status' => true]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-brands.store'), [
            'status' => '1',
            'name' => 'Canon',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, EquipmentBrand::query()->count());
    }

    public function test_silinen_marka_ile_ayni_isim_tekrar_kullanilabilir(): void
    {
        $user = $this->admin();
        $original = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $original->delete();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-brands.store'), [
            'status' => '1',
            'name' => 'Canon',
        ]);

        $response->assertRedirect(route('eys.equipment-brands.index'));
        $this->assertSame(1, EquipmentBrand::query()->count());
    }

    public function test_marka_guncellenebilir(): void
    {
        $user = $this->admin();
        $equipmentBrand = EquipmentBrand::create(['name' => 'Nikon', 'status' => true]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.equipment-brands.update', $equipmentBrand), [
            'status' => '0',
            'name' => 'Nikon (Güncel)',
        ]);

        $response->assertRedirect(route('eys.equipment-brands.index'));

        $equipmentBrand->refresh();
        $this->assertFalse($equipmentBrand->status);
        $this->assertSame('Nikon (Güncel)', $equipmentBrand->name);
    }

    public function test_marka_silinebilir(): void
    {
        $user = $this->admin();
        $equipmentBrand = EquipmentBrand::create(['name' => 'Sony', 'status' => true]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.equipment-brands.destroy', $equipmentBrand));

        $response->assertRedirect(route('eys.equipment-brands.index'));
        $this->assertSoftDeleted($equipmentBrand);
    }

    public function test_izinsiz_kullanici_marka_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-brands.index'));

        $response->assertForbidden();
    }
}
