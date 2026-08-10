<?php

namespace Tests\Feature\Uye;

use App\Models\Photo;
use App\Models\PhotoCategory;
use App\Models\PortfolioSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    private function exifBearingUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'photo.jpg',
            file_get_contents(base_path('tests/Fixtures/photo-with-exif.jpg'))
        );
    }

    private function plainUpload(string $name = 'plain.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            file_get_contents(base_path('tests/Fixtures/photo-without-exif.jpg'))
        );
    }

    public function test_gercek_exifli_fotograf_yuklenince_exif_bilgileri_dogru_okunur(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->exifBearingUpload(),
            'title' => 'Test Fotoğrafı',
            'location' => 'İstanbul',
            'taken_at' => '2024-01-15',
        ]);

        $response->assertRedirect();
        $photo = Photo::query()->firstOrFail();

        $this->assertSame($user->id, $photo->user_id);
        $this->assertFalse($photo->exif_missing);
        $this->assertSame('Canon', $photo->camera_make);
        $this->assertSame('EOS R5', $photo->camera_model);
        $this->assertSame('RF 50mm F1.2 L USM', $photo->lens);
        $this->assertSame('f/1.2', $photo->aperture);
        $this->assertSame('1/200', $photo->shutter_speed);
        $this->assertSame(100, $photo->iso);
        Storage::disk('public')->assertExists($photo->disk_path);

        // exif_raw, yapılandırılmış sütunlara eşlenen dar alt kümeyle sınırlı değil —
        // dosyada okunabilen tüm EXIF etiketlerini içermeli.
        $this->assertArrayHasKey('ExifVersion', $photo->exif_raw);
        $this->assertArrayHasKey('XResolution', $photo->exif_raw);
        $this->assertArrayHasKey('ComponentsConfiguration', $photo->exif_raw);
    }

    public function test_exifsiz_fotograf_yuklenince_exif_missing_isaretlenir_hata_vermez(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'EXIF Yok',
            'location' => 'Ankara',
            'taken_at' => '2024-01-01',
        ]);

        $response->assertRedirect();
        $photo = Photo::query()->firstOrFail();

        $this->assertTrue($photo->exif_missing);
        $this->assertNull($photo->camera_make);
    }

    public function test_30_fotograftan_sonra_31inci_reddedilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Photo::factory()->for($user)->count(30)->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload('extra.jpg'),
            'title' => '31. Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertSame(30, $user->photos()->count());
    }

    public function test_eys_uzerinden_degistirilen_limit_yuklemede_uygulanir(): void
    {
        Storage::fake('public');
        PortfolioSetting::current()->update(['max_photos_per_user' => 2]);
        $user = User::factory()->create();
        Photo::factory()->for($user)->count(2)->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload('third.jpg'),
            'title' => 'Üçüncü Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertSame(2, $user->photos()->count());
    }

    public function test_silme_hem_db_kaydini_hem_disk_dosyasini_temizler(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = Photo::factory()->for($user)->create([
            'disk_path' => 'portfolio/test/photo.jpg',
            'thumb_path' => 'portfolio/test/photo_thumb.jpg',
        ]);
        Storage::disk('public')->put($photo->disk_path, 'fake-content');
        Storage::disk('public')->put($photo->thumb_path, 'fake-content');

        $response = $this->actingAs($user)->delete(route('portfolio.destroy', $photo));

        $response->assertRedirect();
        $this->assertModelMissing($photo);
        Storage::disk('public')->assertMissing($photo->disk_path);
        Storage::disk('public')->assertMissing($photo->thumb_path);
    }

    public function test_baska_kullanicinin_fotografi_duzenlenemez_ve_silinemez(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $photo = Photo::factory()->for($owner)->create();

        $updateResponse = $this->actingAs($intruder)->patch(route('portfolio.update', $photo), [
            'title' => 'Ele geçirildi',
            'location' => 'X',
            'taken_at' => '2024-01-01',
        ]);
        $updateResponse->assertForbidden();

        $deleteResponse = $this->actingAs($intruder)->delete(route('portfolio.destroy', $photo));
        $deleteResponse->assertForbidden();

        $this->assertModelExists($photo);
    }

    public function test_kategoriye_gore_filtrelenebilir(): void
    {
        $user = User::factory()->create();
        $categoryA = PhotoCategory::create(['status' => true]);
        $categoryA->upsertTranslations(['tr' => ['name' => 'Doğa']]);
        $categoryB = PhotoCategory::create(['status' => true]);
        $categoryB->upsertTranslations(['tr' => ['name' => 'Portre']]);

        $matching = Photo::factory()->for($user)->create(['photo_category_id' => $categoryA->id, 'title' => 'Doğa Fotoğrafı']);
        Photo::factory()->for($user)->create(['photo_category_id' => $categoryB->id, 'title' => 'Portre Fotoğrafı']);

        $response = $this->actingAs($user)->get(route('portfolio.index', ['category_id' => $categoryA->id]));

        $response->assertOk();
        $photos = $response->viewData('photos');
        $this->assertCount(1, $photos);
        $this->assertSame($matching->id, $photos->first()->id);
    }

    public function test_anahtar_kelimeye_gore_filtrelenebilir(): void
    {
        $user = User::factory()->create();
        $matching = Photo::factory()->for($user)->create(['title' => 'Gün Batımı Manzarası']);
        Photo::factory()->for($user)->create(['title' => 'Şehir Sokakları']);

        $response = $this->actingAs($user)->get(route('portfolio.index', ['q' => 'Batımı']));

        $photos = $response->viewData('photos');
        $this->assertCount(1, $photos);
        $this->assertSame($matching->id, $photos->first()->id);
    }

    public function test_tarih_araligina_gore_filtrelenebilir(): void
    {
        $user = User::factory()->create();
        $matching = Photo::factory()->for($user)->create(['taken_at' => '2024-06-15']);
        Photo::factory()->for($user)->create(['taken_at' => '2023-01-01']);

        $response = $this->actingAs($user)->get(route('portfolio.index', [
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]));

        $photos = $response->viewData('photos');
        $this->assertCount(1, $photos);
        $this->assertSame($matching->id, $photos->first()->id);
    }
}
