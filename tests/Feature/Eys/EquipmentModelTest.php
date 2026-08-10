<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EquipmentModelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.equipment_models.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.equipment_models.manage');

        return $user;
    }

    private function type(string $name = 'Kamera Gövdesi'): EquipmentType
    {
        $type = EquipmentType::create(['status' => true, 'sort_order' => 10]);
        $type->upsertTranslations(['tr' => ['name' => $name]]);

        return $type;
    }

    public function test_ekipman_modeli_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-models.index'));

        $response->assertOk();
    }

    public function test_yeni_ekipman_modeli_marka_ve_ture_baglanir(): void
    {
        $user = $this->admin();
        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $type = $this->type();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-models.store'), [
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => $type->id,
            'status' => '1',
            'name' => 'EOS R5',
        ]);

        $response->assertRedirect(route('eys.equipment-models.index'));

        $equipmentModel = EquipmentModel::query()->firstOrFail();

        $this->assertSame($brand->id, $equipmentModel->equipment_brand_id);
        $this->assertSame($type->id, $equipmentModel->equipment_type_id);
        $this->assertSame('EOS R5', $equipmentModel->name);
    }

    public function test_gecersiz_markayla_model_olusturulamaz(): void
    {
        $user = $this->admin();
        $type = $this->type();

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-models.store'), [
            'equipment_brand_id' => (string) Str::uuid(),
            'equipment_type_id' => $type->id,
            'status' => '1',
            'name' => 'EOS R5',
        ]);

        $response->assertSessionHasErrors('equipment_brand_id');
    }

    public function test_gecersiz_turle_model_olusturulamaz(): void
    {
        $user = $this->admin();
        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-models.store'), [
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => (string) Str::uuid(),
            'status' => '1',
            'name' => 'EOS R5',
        ]);

        $response->assertSessionHasErrors('equipment_type_id');
    }

    public function test_ayni_markada_ayni_isimde_model_tekrar_olusturulamaz(): void
    {
        $user = $this->admin();
        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $type = $this->type();
        EquipmentModel::create([
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => $type->id,
            'name' => 'EOS R5',
            'status' => true,
        ]);

        $response = $this->actingAs($user, 'eys')->post(route('eys.equipment-models.store'), [
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => $type->id,
            'status' => '1',
            'name' => 'EOS R5',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_marka_ve_ture_gore_filtrelenebilir(): void
    {
        $user = $this->admin();
        $canon = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $nikon = EquipmentBrand::create(['name' => 'Nikon', 'status' => true]);
        $body = $this->type('Kamera Gövdesi');
        $lens = $this->type('Objektif');

        $matching = EquipmentModel::create(['equipment_brand_id' => $canon->id, 'equipment_type_id' => $body->id, 'name' => 'EOS R5', 'status' => true]);
        EquipmentModel::create(['equipment_brand_id' => $nikon->id, 'equipment_type_id' => $body->id, 'name' => 'Z9', 'status' => true]);
        EquipmentModel::create(['equipment_brand_id' => $canon->id, 'equipment_type_id' => $lens->id, 'name' => 'RF 50mm F1.2L', 'status' => true]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-models.index', ['equipment_brand_id' => $canon->id, 'equipment_type_id' => $body->id]));

        $response->assertOk();
        $equipmentModels = $response->viewData('equipmentModels');
        $this->assertSame(1, $equipmentModels->total());
        $this->assertSame($matching->id, $equipmentModels->first()->id);
    }

    public function test_izinsiz_kullanici_ekipman_modeli_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.equipment-models.index'));

        $response->assertForbidden();
    }
}
