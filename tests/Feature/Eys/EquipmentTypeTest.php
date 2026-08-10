<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EquipmentType;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EquipmentTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.equipment_types.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.equipment_types.manage');

        return $user;
    }

    public function test_ekipman_turu_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-types.index'));

        $response->assertOk();
    }

    public function test_yeni_ekipman_turu_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-types.store'), [
            'status' => '1',
            'sort_order' => '15',
            'tr' => ['name' => 'Objektif'],
            'en' => ['name' => 'Lens'],
        ]);

        $response->assertRedirect(route('eys.equipment-types.index'));

        $equipmentType = EquipmentType::query()->firstOrFail();

        $this->assertSame(15, $equipmentType->sort_order);
        $this->assertSame('Objektif', $equipmentType->getTranslation('tr')?->name);
        $this->assertSame('Lens', $equipmentType->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-types.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_ekipman_turu_guncellenebilir(): void
    {
        $user = $this->admin();

        $equipmentType = EquipmentType::create(['status' => true, 'sort_order' => 10]);
        $equipmentType->upsertTranslations(['tr' => ['name' => 'Flaş'], 'en' => ['name' => 'Flash']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.equipment-types.update', $equipmentType), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Flaş (Güncel)'],
            'en' => ['name' => 'Flash (Updated)'],
        ]);

        $response->assertRedirect(route('eys.equipment-types.index'));

        $equipmentType->refresh();
        $this->assertFalse($equipmentType->status);
        $this->assertSame(99, $equipmentType->sort_order);
        $this->assertSame('Flaş (Güncel)', $equipmentType->getTranslation('tr')?->name);
    }

    public function test_ekipman_turu_silinebilir(): void
    {
        $user = $this->admin();

        $equipmentType = EquipmentType::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.equipment-types.destroy', $equipmentType));

        $response->assertRedirect(route('eys.equipment-types.index'));
        $this->assertSoftDeleted($equipmentType);
    }

    public function test_izinsiz_kullanici_ekipman_turu_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-types.index'));

        $response->assertForbidden();
    }
}
