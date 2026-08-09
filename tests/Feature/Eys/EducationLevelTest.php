<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EducationLevel;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EducationLevelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.education_levels.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.education_levels.manage');

        return $user;
    }

    public function test_ogrenim_durumu_listesi_goruntulenebilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->get(route('eys.education-levels.index'));

        $response->assertOk();
    }

    public function test_yeni_ogrenim_durumu_olusturulabilir_ve_cevirileri_kaydedilir(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.education-levels.store'), [
            'status' => '1',
            'sort_order' => '15',
            'tr' => ['name' => 'İlkokul'],
            'en' => ['name' => 'Primary School'],
        ]);

        $response->assertRedirect(route('eys.education-levels.index'));

        $educationLevel = EducationLevel::query()->firstOrFail();

        $this->assertSame(15, $educationLevel->sort_order);
        $this->assertSame('İlkokul', $educationLevel->getTranslation('tr')?->name);
        $this->assertSame('Primary School', $educationLevel->getTranslation('en')?->name);
    }

    public function test_varsayilan_dilde_ad_zorunludur(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user, 'eys')->post(route('eys.education-levels.store'), [
            'status' => '1',
            'tr' => ['name' => ''],
        ]);

        $response->assertSessionHasErrors('tr.name');
    }

    public function test_ogrenim_durumu_guncellenebilir(): void
    {
        $user = $this->admin();

        $educationLevel = EducationLevel::create(['status' => true, 'sort_order' => 10]);
        $educationLevel->upsertTranslations(['tr' => ['name' => 'Lise'], 'en' => ['name' => 'High School']]);

        $response = $this->actingAs($user, 'eys')->patch(route('eys.education-levels.update', $educationLevel), [
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Lise (Güncel)'],
            'en' => ['name' => 'High School (Updated)'],
        ]);

        $response->assertRedirect(route('eys.education-levels.index'));

        $educationLevel->refresh();
        $this->assertFalse($educationLevel->status);
        $this->assertSame(99, $educationLevel->sort_order);
        $this->assertSame('Lise (Güncel)', $educationLevel->getTranslation('tr')?->name);
    }

    public function test_ogrenim_durumu_silinebilir(): void
    {
        $user = $this->admin();

        $educationLevel = EducationLevel::create(['status' => true, 'sort_order' => 10]);

        $response = $this->actingAs($user, 'eys')->delete(route('eys.education-levels.destroy', $educationLevel));

        $response->assertRedirect(route('eys.education-levels.index'));
        $this->assertSoftDeleted($educationLevel);
    }

    public function test_isme_ve_duruma_gore_filtrelenebilir(): void
    {
        $user = $this->admin();

        $lise = EducationLevel::create(['status' => true, 'sort_order' => 30]);
        $lise->upsertTranslations(['tr' => ['name' => 'Lise'], 'en' => ['name' => 'High School']]);

        $lisans = EducationLevel::create(['status' => false, 'sort_order' => 50]);
        $lisans->upsertTranslations(['tr' => ['name' => 'Lisans'], 'en' => ['name' => "Bachelor's Degree"]]);

        $response = $this->actingAs($user, 'eys')->get(route('eys.education-levels.index', ['name' => 'Lise']));

        $response->assertOk();
        $educationLevels = $response->viewData('educationLevels');
        $this->assertSame(1, $educationLevels->total());

        $response = $this->actingAs($user, 'eys')->get(route('eys.education-levels.index', ['status' => '0']));

        $response->assertOk();
        $educationLevels = $response->viewData('educationLevels');
        $this->assertSame(1, $educationLevels->total());
        $this->assertFalse($educationLevels->first()->status);
    }

    public function test_izinsiz_kullanici_ogrenim_durumu_sayfasina_erisemez(): void
    {
        $user = EysUser::factory()->create();

        $response = $this->actingAs($user, 'eys')->get(route('eys.education-levels.index'));

        $response->assertForbidden();
    }
}
