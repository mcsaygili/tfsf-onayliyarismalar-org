<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\Permission;
use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegulationItemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.regulation_items.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.regulation_items.manage');

        return $user;
    }

    private function section(string $name = 'Yarışma Koşulları'): RegulationSection
    {
        $section = RegulationSection::create(['status' => true, 'sort_order' => 10]);
        $section->upsertTranslations(['tr' => ['name' => $name]]);

        return $section;
    }

    public function test_madde_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();
        $section = $this->section();
        $section->items()->create(['sort_order' => 1, 'status' => true]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-items.index'));

        $response->assertOk();
    }

    public function test_yeni_madde_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();
        $section = $this->section();

        $response = $this->actingAs($user, 'eys')->post(route('eys.regulation-items.store'), [
            'regulation_section_id' => $section->id,
            'sort_order' => '1',
            'code' => 'a',
            'status' => '1',
            'content_type' => 'fixed',
            'tr' => ['content' => 'Yarışmaya katılım ücretsizdir.'],
            'en' => ['content' => 'Participation in the competition is free.'],
        ]);

        $response->assertRedirect(route('eys.regulation-items.index'));

        $item = RegulationItem::query()->firstOrFail();

        $this->assertSame($section->id, $item->regulation_section_id);
        $this->assertSame('a', $item->code);
        $this->assertSame('Yarışmaya katılım ücretsizdir.', $item->getTranslation('tr')?->content);
        $this->assertSame('Participation in the competition is free.', $item->getTranslation('en')?->content);
    }

    public function test_bolum_secimi_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.regulation-items.store'), [
            'status' => '1',
            'tr' => ['content' => 'İçerik'],
        ]);

        $response->assertSessionHasErrors('regulation_section_id');
    }

    public function test_varsayilan_dilde_icerik_zorunludur(): void
    {
        $user = $this->admin();
        $section = $this->section();

        $response = $this->actingAs($user, 'eys')->post(route('eys.regulation-items.store'), [
            'regulation_section_id' => $section->id,
            'status' => '1',
            'tr' => ['content' => ''],
        ]);

        $response->assertSessionHasErrors('tr.content');
    }

    public function test_madde_guncellenebilir_ve_silinebilir(): void
    {
        $user = $this->admin();
        $section = $this->section();
        $item = $section->items()->create(['sort_order' => 1, 'status' => true]);
        $item->upsertTranslations(['tr' => ['content' => 'Eski içerik.']]);

        $otherSection = $this->section('Telif Hakkı');

        $updateResponse = $this->actingAs($user, 'eys')->patch(route('eys.regulation-items.update', $item), [
            'regulation_section_id' => $otherSection->id,
            'sort_order' => '2',
            'status' => '0',
            'content_type' => 'fixed',
            'tr' => ['content' => 'Yeni içerik.'],
            'en' => ['content' => 'Updated content.'],
        ]);

        $updateResponse->assertRedirect(route('eys.regulation-items.index'));
        $item->refresh();
        $this->assertSame($otherSection->id, $item->regulation_section_id);
        $this->assertFalse($item->status);
        $this->assertSame('Yeni içerik.', $item->getTranslation('tr')?->content);

        $deleteResponse = $this->actingAs($user, 'eys')->delete(route('eys.regulation-items.destroy', $item));
        $deleteResponse->assertRedirect(route('eys.regulation-items.index'));
        $this->assertSoftDeleted($item);
    }

    public function test_bolume_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();
        $kosullar = $this->section('Yarışma Koşulları');
        $telif = $this->section('Telif Hakkı');

        $item1 = $kosullar->items()->create(['sort_order' => 1, 'status' => true]);
        $item1->upsertTranslations(['tr' => ['content' => 'Katılım ücretsizdir.']]);

        $item2 = $telif->items()->create(['sort_order' => 1, 'status' => false]);
        $item2->upsertTranslations(['tr' => ['content' => 'Telif ile ilgili madde.']]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-items.index', ['regulation_section_id' => $kosullar->id]));

        $response->assertOk();
        $items = $response->viewData('items');
        $this->assertSame(1, $items->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-items.index', ['status' => '0']));

        $response->assertOk();
        $items = $response->viewData('items');
        $this->assertSame(1, $items->total());
        $this->assertFalse($items->first()->status);
    }

    public function test_izinsiz_kullanici_maddeler_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.regulation-items.index'));

        $response->assertForbidden();
    }
}
