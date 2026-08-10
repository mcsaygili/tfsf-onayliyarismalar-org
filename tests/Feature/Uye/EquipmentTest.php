<?php

namespace Tests\Feature\Uye;

use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private function equipmentModel(bool $active = true): EquipmentModel
    {
        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $type = EquipmentType::create(['status' => true, 'sort_order' => 10]);
        $type->upsertTranslations(['tr' => ['name' => 'Kamera Gövdesi']]);

        return EquipmentModel::create([
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => $type->id,
            'name' => 'EOS R5',
            'status' => $active,
        ]);
    }

    public function test_kendi_ekipman_listesi_goruntulenir_baskasininki_gorunmez(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $model = $this->equipmentModel();

        UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id]);
        UserEquipment::create(['user_id' => $other->id, 'equipment_model_id' => $model->id]);

        $response = $this->actingAs($user)->get(route('equipment.index'));

        $response->assertOk();
        $equipment = $response->viewData('equipment');
        $this->assertCount(1, $equipment);
        $this->assertSame($user->id, $equipment->first()->user_id);
    }

    public function test_ekleme_sayfasi_goruntulenebilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('equipment.create'));

        $response->assertOk();
    }

    public function test_duzenleme_sayfasi_sadece_sahibine_acik(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $model = $this->equipmentModel();
        $userEquipment = UserEquipment::create(['user_id' => $owner->id, 'equipment_model_id' => $model->id]);

        $ownerResponse = $this->actingAs($owner)->get(route('equipment.edit', $userEquipment));
        $ownerResponse->assertOk();

        $intruderResponse = $this->actingAs($intruder)->get(route('equipment.edit', $userEquipment));
        $intruderResponse->assertForbidden();
    }

    public function test_yeni_ekipman_eklenebilir(): void
    {
        $user = User::factory()->create();
        $model = $this->equipmentModel();

        $response = $this->actingAs($user)->post(route('equipment.store'), [
            'equipment_model_id' => $model->id,
            'notes' => 'Ana gövdem',
        ]);

        $response->assertRedirect();

        $userEquipment = UserEquipment::query()->firstOrFail();
        $this->assertSame($user->id, $userEquipment->user_id);
        $this->assertSame($model->id, $userEquipment->equipment_model_id);
        $this->assertSame('Ana gövdem', $userEquipment->notes);
    }

    public function test_pasif_model_ile_ekipman_eklenemez(): void
    {
        $user = User::factory()->create();
        $model = $this->equipmentModel(active: false);

        $response = $this->actingAs($user)->post(route('equipment.store'), [
            'equipment_model_id' => $model->id,
        ]);

        $response->assertSessionHasErrors('equipment_model_id');
        $this->assertSame(0, UserEquipment::query()->count());
    }

    public function test_var_olmayan_model_ile_ekipman_eklenemez(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('equipment.store'), [
            'equipment_model_id' => (string) Str::uuid(),
        ]);

        $response->assertSessionHasErrors('equipment_model_id');
    }

    public function test_notlar_guncellenebilir(): void
    {
        $user = User::factory()->create();
        $model = $this->equipmentModel();
        $userEquipment = UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id, 'notes' => 'Eski not']);

        $response = $this->actingAs($user)->patch(route('equipment.update', $userEquipment), [
            'notes' => 'Yeni not',
        ]);

        $response->assertRedirect();
        $this->assertSame('Yeni not', $userEquipment->fresh()->notes);
    }

    public function test_ekipman_silinebilir(): void
    {
        $user = User::factory()->create();
        $model = $this->equipmentModel();
        $userEquipment = UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id]);

        $response = $this->actingAs($user)->delete(route('equipment.destroy', $userEquipment));

        $response->assertRedirect();
        $this->assertModelMissing($userEquipment);
    }

    public function test_baskasinin_ekipmanini_duzenleyemez_ve_silemez(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $model = $this->equipmentModel();
        $userEquipment = UserEquipment::create(['user_id' => $owner->id, 'equipment_model_id' => $model->id]);

        $updateResponse = $this->actingAs($intruder)->patch(route('equipment.update', $userEquipment), ['notes' => 'Ele geçirildi']);
        $updateResponse->assertForbidden();

        $deleteResponse = $this->actingAs($intruder)->delete(route('equipment.destroy', $userEquipment));
        $deleteResponse->assertForbidden();

        $this->assertModelExists($userEquipment);
        $this->assertNotSame('Ele geçirildi', $userEquipment->fresh()->notes);
    }

    public function test_markanin_aktif_modelleri_json_olarak_donebilir(): void
    {
        $user = User::factory()->create();
        $brand = EquipmentBrand::create(['name' => 'Sony', 'status' => true]);
        $type = EquipmentType::create(['status' => true]);
        $type->upsertTranslations(['tr' => ['name' => 'Kamera Gövdesi']]);

        $active = EquipmentModel::create(['equipment_brand_id' => $brand->id, 'equipment_type_id' => $type->id, 'name' => 'A7 IV', 'status' => true]);
        EquipmentModel::create(['equipment_brand_id' => $brand->id, 'equipment_type_id' => $type->id, 'name' => 'A7 III (Pasif)', 'status' => false]);

        $response = $this->actingAs($user)->getJson(route('equipment.models-by-brand', $brand));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $active->id, 'name' => 'A7 IV']);
    }
}
