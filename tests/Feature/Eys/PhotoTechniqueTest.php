<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\PhotoTechnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhotoTechniqueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.photo_techniques.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.photo_techniques.manage');

        return $user;
    }

    public function test_cekim_teknigi_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-techniques.index'));

        $response->assertOk();
    }

    public function test_yeni_cekim_teknigi_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.photo-techniques.store'), [
            'status' => '1',
            'sort_order' => '15',
            'tr' => ['name' => 'HDR Kullanıldı'],
            'en' => ['name' => 'HDR Used'],
        ]);

        $response->assertRedirect(route('eys.photo-techniques.index'));

        $photoTechnique = PhotoTechnique::query()->firstOrFail();

        $this->assertSame(15, $photoTechnique->sort_order);
        $this->assertSame('HDR Kullanıldı', $photoTechnique->getTranslation('tr')?->name);
        $this->assertSame('HDR Used', $photoTechnique->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.photo-techniques.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_cekim_teknigi_guncellenebilir(): void
    {
        $user = $this->admin();

        $photoTechnique = PhotoTechnique::create(['status' => true, 'sort_order' => 10]);
        $photoTechnique->upsertTranslations(['tr' => ['name' => 'Panorama'], 'en' => ['name' => 'Panorama']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.photo-techniques.update', $photoTechnique), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Panorama (Güncel)'],
            'en' => ['name' => 'Panorama (Updated)'],
        ]);

        $response->assertRedirect(route('eys.photo-techniques.index'));

        $photoTechnique->refresh();
        $this->assertFalse($photoTechnique->status);
        $this->assertSame(99, $photoTechnique->sort_order);
        $this->assertSame('Panorama (Güncel)', $photoTechnique->getTranslation('tr')?->name);
    }

    public function test_cekim_teknigi_silinebilir(): void
    {
        $user = $this->admin();

        $photoTechnique = PhotoTechnique::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.photo-techniques.destroy', $photoTechnique));

        $response->assertRedirect(route('eys.photo-techniques.index'));
        $this->assertSoftDeleted($photoTechnique);
    }

    public function test_isme_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();

        $hdr = PhotoTechnique::create(['status' => true, 'sort_order' => 30]);
        $hdr->upsertTranslations(['tr' => ['name' => 'HDR Kullanıldı'], 'en' => ['name' => 'HDR Used']]);

        $ai = PhotoTechnique::create(['status' => false, 'sort_order' => 50]);
        $ai->upsertTranslations(['tr' => ['name' => 'Yapay Zekâ Kullanıldı'], 'en' => ['name' => 'AI Used']]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-techniques.index', ['name' => 'HDR']));

        $response->assertOk();
        $photoTechniques = $response->viewData('photoTechniques');
        $this->assertSame(1, $photoTechniques->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-techniques.index', ['status' => '0']));

        $response->assertOk();
        $photoTechniques = $response->viewData('photoTechniques');
        $this->assertSame(1, $photoTechniques->total());
        $this->assertFalse($photoTechniques->first()->status);
    }

    public function test_izinsiz_kullanici_cekim_teknigi_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.photo-techniques.index'));

        $response->assertForbidden();
    }
}
