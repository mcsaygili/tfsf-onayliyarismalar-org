<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\PhotoCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhotoCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.photo_categories.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.photo_categories.manage');

        return $user;
    }

    public function test_fotograf_kategorisi_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-categories.index'));

        $response->assertOk();
    }

    public function test_yeni_fotograf_kategorisi_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.photo-categories.store'), [
            'status' => '1',
            'sort_order' => '15',
            'tr' => ['name' => 'Doğa'],
            'en' => ['name' => 'Nature'],
        ]);

        $response->assertRedirect(route('eys.photo-categories.index'));

        $photoCategory = PhotoCategory::query()->firstOrFail();

        $this->assertSame(15, $photoCategory->sort_order);
        $this->assertSame('Doğa', $photoCategory->getTranslation('tr')?->name);
        $this->assertSame('Nature', $photoCategory->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.photo-categories.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_fotograf_kategorisi_guncellenebilir(): void
    {
        $user = $this->admin();

        $photoCategory = PhotoCategory::create(['status' => true, 'sort_order' => 10]);
        $photoCategory->upsertTranslations(['tr' => ['name' => 'Portre'], 'en' => ['name' => 'Portrait']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.photo-categories.update', $photoCategory), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Portre (Güncel)'],
            'en' => ['name' => 'Portrait (Updated)'],
        ]);

        $response->assertRedirect(route('eys.photo-categories.index'));

        $photoCategory->refresh();
        $this->assertFalse($photoCategory->status);
        $this->assertSame(99, $photoCategory->sort_order);
        $this->assertSame('Portre (Güncel)', $photoCategory->getTranslation('tr')?->name);
    }

    public function test_fotograf_kategorisi_silinebilir(): void
    {
        $user = $this->admin();

        $photoCategory = PhotoCategory::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.photo-categories.destroy', $photoCategory));

        $response->assertRedirect(route('eys.photo-categories.index'));
        $this->assertSoftDeleted($photoCategory);
    }

    public function test_isme_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();

        $portre = PhotoCategory::create(['status' => true, 'sort_order' => 30]);
        $portre->upsertTranslations(['tr' => ['name' => 'Portre'], 'en' => ['name' => 'Portrait']]);

        $soyut = PhotoCategory::create(['status' => false, 'sort_order' => 50]);
        $soyut->upsertTranslations(['tr' => ['name' => 'Soyut'], 'en' => ['name' => 'Abstract']]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-categories.index', ['name' => 'Portre']));

        $response->assertOk();
        $photoCategories = $response->viewData('photoCategories');
        $this->assertSame(1, $photoCategories->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-categories.index', ['status' => '0']));

        $response->assertOk();
        $photoCategories = $response->viewData('photoCategories');
        $this->assertSame(1, $photoCategories->total());
        $this->assertFalse($photoCategories->first()->status);
    }

    public function test_izinsiz_kullanici_fotograf_kategorisi_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-categories.index'));

        $response->assertForbidden();
    }
}
