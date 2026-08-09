<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EysUser;
use App\Models\InstitutionType;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstitutionTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.institution_types.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.institution_types.manage');

        return $user;
    }

    public function test_kurum_turu_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution-types.index'));

        $response->assertOk();
    }

    public function test_yeni_kurum_turu_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.institution-types.store'), [
            'status' => '1',
            'sort_order' => '15',
            'tr' => ['name' => 'Dernek'],
            'en' => ['name' => 'Association'],
        ]);

        $response->assertRedirect(route('eys.institution-types.index'));

        $institutionType = InstitutionType::query()->firstOrFail();

        $this->assertSame(15, $institutionType->sort_order);
        $this->assertSame('Dernek', $institutionType->getTranslation('tr')?->name);
        $this->assertSame('Association', $institutionType->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.institution-types.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_kurum_turu_guncellenebilir(): void
    {
        $user = $this->admin();

        $institutionType = InstitutionType::create(['status' => true, 'sort_order' => 10]);
        $institutionType->upsertTranslations(['tr' => ['name' => 'Vakıf'], 'en' => ['name' => 'Foundation']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.institution-types.update', $institutionType), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Vakıf (Güncel)'],
            'en' => ['name' => 'Foundation (Updated)'],
        ]);

        $response->assertRedirect(route('eys.institution-types.index'));

        $institutionType->refresh();
        $this->assertFalse($institutionType->status);
        $this->assertSame(99, $institutionType->sort_order);
        $this->assertSame('Vakıf (Güncel)', $institutionType->getTranslation('tr')?->name);
    }

    public function test_kurum_turu_silinebilir(): void
    {
        $user = $this->admin();

        $institutionType = InstitutionType::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.institution-types.destroy', $institutionType));

        $response->assertRedirect(route('eys.institution-types.index'));
        $this->assertSoftDeleted($institutionType);
    }

    public function test_isme_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();

        $vakif = InstitutionType::create(['status' => true, 'sort_order' => 40]);
        $vakif->upsertTranslations(['tr' => ['name' => 'Vakıf'], 'en' => ['name' => 'Foundation']]);

        $belediye = InstitutionType::create(['status' => false, 'sort_order' => 20]);
        $belediye->upsertTranslations(['tr' => ['name' => 'Belediye'], 'en' => ['name' => 'Municipality']]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution-types.index', ['name' => 'Vakıf']));

        $response->assertOk();
        $institutionTypes = $response->viewData('institutionTypes');
        $this->assertSame(1, $institutionTypes->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution-types.index', ['status' => '0']));

        $response->assertOk();
        $institutionTypes = $response->viewData('institutionTypes');
        $this->assertSame(1, $institutionTypes->total());
        $this->assertFalse($institutionTypes->first()->status);
    }

    public function test_izinsiz_kullanici_kurum_turu_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.institution-types.index'));

        $response->assertForbidden();
    }
}
