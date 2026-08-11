<?php

namespace Tests\Feature\Uye;

use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_gosterge_paneli_istatistikleri_dogru_gosterilir(): void
    {
        $user = User::factory()->create();
        Photo::factory()->for($user)->count(3)->create();

        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $type = EquipmentType::create(['status' => true]);
        $type->upsertTranslations(['tr' => ['name' => 'Kamera Gövdesi']]);
        $model = EquipmentModel::create(['equipment_brand_id' => $brand->id, 'equipment_type_id' => $type->id, 'name' => 'EOS R5', 'status' => true]);
        UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id]);
        UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('photoCount', 3);
        $response->assertViewHas('equipmentCount', 2);
        $response->assertViewHas('maxPhotos');
        $response->assertViewHas('memberSince');
    }

    public function test_ilk_giris_oncesi_son_giris_bos_gosterilir(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('lastLoginAt', null);
        $response->assertSee(__('uye.dashboard.last_login_never'));
    }

    public function test_giriste_son_giris_tarihi_guncellenir(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
