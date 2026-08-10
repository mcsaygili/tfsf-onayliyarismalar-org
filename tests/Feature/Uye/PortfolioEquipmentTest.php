<?php

namespace Tests\Feature\Uye;

use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fotoğraf↔Ekipman etiketleme testleri — PortfolioTest.php'nin şişmemesi
 * için ayrı dosyada. IDOR testi (başkasının ekipmanını etiketleme denemesi)
 * PhotoUploadRequest/PhotoUpdateRequest'teki sahiplik-kapsamlı Rule::exists()
 * kuralını doğrudan doğruluyor.
 */
class PortfolioEquipmentTest extends TestCase
{
    use RefreshDatabase;

    private function plainUpload(string $name = 'plain.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            file_get_contents(base_path('tests/Fixtures/photo-without-exif.jpg'))
        );
    }

    private function userEquipmentFor(User $user): UserEquipment
    {
        $brand = EquipmentBrand::create(['name' => 'Canon', 'status' => true]);
        $type = EquipmentType::create(['status' => true]);
        $type->upsertTranslations(['tr' => ['name' => 'Kamera Gövdesi']]);
        $model = EquipmentModel::create([
            'equipment_brand_id' => $brand->id,
            'equipment_type_id' => $type->id,
            'name' => 'EOS R5',
            'status' => true,
        ]);

        return UserEquipment::create(['user_id' => $user->id, 'equipment_model_id' => $model->id]);
    }

    public function test_fotograf_yuklerken_kendi_ekipmanlari_etiketlenebilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $itemA = $this->userEquipmentFor($user);
        $itemB = $this->userEquipmentFor($user);

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Etiketli Fotoğraf',
            'location' => 'İstanbul',
            'taken_at' => '2024-01-01',
            'equipment' => [$itemA->id, $itemB->id],
        ]);

        $response->assertRedirect();

        $photo = Photo::query()->firstOrFail();
        $this->assertCount(2, $photo->equipment);
        $this->assertEqualsCanonicalizing([$itemA->id, $itemB->id], $photo->equipment->pluck('id')->all());
    }

    public function test_fotograf_duzenlerken_ekipman_etiketleri_guncellenebilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $itemA = $this->userEquipmentFor($user);
        $itemB = $this->userEquipmentFor($user);

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'equipment' => [$itemA->id],
        ]);
        $response->assertRedirect();
        $photo = Photo::query()->firstOrFail();
        $this->assertSame([$itemA->id], $photo->equipment->pluck('id')->all());

        $updateResponse = $this->actingAs($user)->patch(route('portfolio.update', $photo), [
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'equipment' => [$itemB->id],
        ]);
        $updateResponse->assertRedirect();

        $photo->refresh();
        $this->assertSame([$itemB->id], $photo->equipment->pluck('id')->all());
    }

    public function test_baskasinin_ekipmanini_fotografa_etiketlemeye_calisinca_reddedilir(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $foreignItem = $this->userEquipmentFor($owner);

        $storeResponse = $this->actingAs($intruder)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'equipment' => [$foreignItem->id],
        ]);

        $storeResponse->assertSessionHasErrors('equipment.0');
        $this->assertSame(0, Photo::query()->count());
        $this->assertDatabaseMissing('photo_equipment', ['user_equipment_id' => $foreignItem->id]);

        $ownPhoto = Photo::factory()->for($intruder)->create();
        $updateResponse = $this->actingAs($intruder)->patch(route('portfolio.update', $ownPhoto), [
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'equipment' => [$foreignItem->id],
        ]);

        $updateResponse->assertSessionHasErrors('equipment.0');
        $this->assertDatabaseMissing('photo_equipment', ['photo_id' => $ownPhoto->id, 'user_equipment_id' => $foreignItem->id]);
    }

    public function test_var_olmayan_ekipman_idsi_reddedilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'equipment' => [(string) Str::uuid()],
        ]);

        $response->assertSessionHasErrors('equipment.0');
        $this->assertSame(0, Photo::query()->count());
    }
}
