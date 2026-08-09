<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\RegulationSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegulationSectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.regulation_sections.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.regulation_sections.manage');

        return $user;
    }

    public function test_bolum_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-sections.index'));

        $response->assertOk();
    }

    public function test_yeni_bolum_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.regulation-sections.store'), [
            'status' => '1',
            'sort_order' => '50',
            'tr' => ['name' => 'Yarışma Koşulları'],
            'en' => ['name' => 'Competition Conditions'],
        ]);

        $response->assertRedirect(route('eys.regulation-sections.index'));

        $section = RegulationSection::query()->firstOrFail();

        $this->assertSame(50, $section->sort_order);
        $this->assertSame('Yarışma Koşulları', $section->getTranslation('tr')?->name);
        $this->assertSame('Competition Conditions', $section->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.regulation-sections.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_bolum_guncellenebilir(): void
    {
        $user = $this->admin();

        $section = RegulationSection::create(['status' => true, 'sort_order' => 10]);
        $section->upsertTranslations(['tr' => ['name' => 'Eski Ad'], 'en' => ['name' => 'Old Name']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.regulation-sections.update', $section), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Yeni Ad'],
            'en' => ['name' => 'New Name'],
        ]);

        $response->assertRedirect(route('eys.regulation-sections.index'));

        $section->refresh();
        $this->assertFalse($section->status);
        $this->assertSame(99, $section->sort_order);
        $this->assertSame('Yeni Ad', $section->getTranslation('tr')?->name);
    }

    public function test_maddesi_olan_bolum_silinemez(): void
    {
        $user = $this->admin();

        $section = RegulationSection::create(['status' => true, 'sort_order' => 10]);
        $item = $section->items()->create(['sort_order' => 1, 'status' => true]);
        $item->upsertTranslations(['tr' => ['content' => 'Örnek madde metni.']]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.regulation-sections.destroy', $section));

        $response->assertRedirect(route('eys.regulation-sections.index'));
        $this->assertModelExists($section);
    }

    public function test_maddesi_olmayan_bolum_silinebilir(): void
    {
        $user = $this->admin();

        $section = RegulationSection::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.regulation-sections.destroy', $section));

        $response->assertRedirect(route('eys.regulation-sections.index'));
        $this->assertSoftDeleted($section);
    }

    public function test_isme_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();

        $kosullar = RegulationSection::create(['status' => true, 'sort_order' => 50]);
        $kosullar->upsertTranslations(['tr' => ['name' => 'Yarışma Koşulları']]);

        $telif = RegulationSection::create(['status' => false, 'sort_order' => 60]);
        $telif->upsertTranslations(['tr' => ['name' => 'Telif Hakkı']]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-sections.index', ['name' => 'Koşulları']));

        $response->assertOk();
        $sections = $response->viewData('sections');
        $this->assertSame(1, $sections->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-sections.index', ['status' => '0']));

        $response->assertOk();
        $sections = $response->viewData('sections');
        $this->assertSame(1, $sections->total());
        $this->assertFalse($sections->first()->status);
    }

    public function test_izinsiz_kullanici_bolumler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-sections.index'));

        $response->assertForbidden();
    }
}
