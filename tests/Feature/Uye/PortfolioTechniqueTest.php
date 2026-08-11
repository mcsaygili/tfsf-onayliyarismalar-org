<?php

namespace Tests\Feature\Uye;

use App\Models\Photo;
use App\Models\PhotoTechnique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fotoğraf↔Çekim Tekniği etiketleme testleri — ayrı dosyada (bkz.
 * PortfolioEquipmentTest.php ile aynı gerekçe). Ekipman etiketlemesinden
 * farklı olarak sahiplik kapsamı YOK — PhotoTechnique global bir referans
 * kataloğu, herhangi bir kullanıcı herhangi bir aktif tekniği seçebilir.
 */
class PortfolioTechniqueTest extends TestCase
{
    use RefreshDatabase;

    private function plainUpload(string $name = 'plain.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            file_get_contents(base_path('tests/Fixtures/photo-without-exif.jpg'))
        );
    }

    private function technique(bool $active = true): PhotoTechnique
    {
        $technique = PhotoTechnique::create(['status' => $active, 'sort_order' => 10]);
        $technique->upsertTranslations(['tr' => ['name' => 'HDR Kullanıldı'], 'en' => ['name' => 'HDR Used']]);

        return $technique;
    }

    public function test_fotograf_yuklerken_teknikler_etiketlenebilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $techniqueA = $this->technique();
        $techniqueB = $this->technique();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Teknik Beyanlı Fotoğraf',
            'location' => 'İstanbul',
            'taken_at' => '2024-01-01',
            'techniques' => [$techniqueA->id, $techniqueB->id],
        ]);

        $response->assertRedirect();

        $photo = Photo::query()->firstOrFail();
        $this->assertCount(2, $photo->techniques);
        $this->assertEqualsCanonicalizing([$techniqueA->id, $techniqueB->id], $photo->techniques->pluck('id')->all());
    }

    public function test_fotograf_duzenlerken_teknikler_guncellenebilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $techniqueA = $this->technique();
        $techniqueB = $this->technique();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'techniques' => [$techniqueA->id],
        ]);
        $response->assertRedirect();
        $photo = Photo::query()->firstOrFail();
        $this->assertSame([$techniqueA->id], $photo->techniques->pluck('id')->all());

        $updateResponse = $this->actingAs($user)->patch(route('portfolio.update', $photo), [
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'techniques' => [$techniqueB->id],
        ]);
        $updateResponse->assertRedirect();

        $photo->refresh();
        $this->assertSame([$techniqueB->id], $photo->techniques->pluck('id')->all());
    }

    public function test_pasif_teknik_reddedilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $inactive = $this->technique(active: false);

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'techniques' => [$inactive->id],
        ]);

        $response->assertSessionHasErrors('techniques.0');
        $this->assertSame(0, Photo::query()->count());
    }

    public function test_var_olmayan_teknik_idsi_reddedilir(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('portfolio.store'), [
            'photo' => $this->plainUpload(),
            'title' => 'Fotoğraf',
            'location' => 'X',
            'taken_at' => '2024-01-01',
            'techniques' => [(string) Str::uuid()],
        ]);

        $response->assertSessionHasErrors('techniques.0');
        $this->assertSame(0, Photo::query()->count());
    }
}
