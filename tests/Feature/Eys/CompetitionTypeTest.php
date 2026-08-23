<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\CompetitionType;
use App\Models\EysUser;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompetitionTypeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.competition_types.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.competition_types.manage');

        return $user;
    }

    public function test_yarisma_turu_listesi_goruntulenebilir(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->get(route('eys.competition-types.index'));

        $response->assertOk();
        $response->assertSee(__('eys.nav.section_competition_system'));
        $response->assertSee(__('eys.nav.reference_data'));
        $response->assertSee(__('eys.nav.competition_types'));
    }

    public function test_yeni_yarisma_turu_tr_ve_en_cevirileriyle_olusturulabilir(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->post(route('eys.competition-types.store'), [
            'code' => 'standard',
            'status' => '1',
            'sort_order' => '10',
            'tr' => ['name' => 'Standart Yarışma', 'description' => 'Türkçe açıklama'],
            'en' => ['name' => 'Standard Competition', 'description' => 'English description'],
        ]);

        $response->assertRedirect(route('eys.competition-types.index'));

        $competitionType = CompetitionType::query()->firstOrFail();
        $this->assertSame('standard', $competitionType->code);
        $this->assertSame('Standart Yarışma', $competitionType->getTranslation('tr')?->name);
        $this->assertSame('Türkçe açıklama', $competitionType->getTranslation('tr')?->description);
        $this->assertSame('Standard Competition', $competitionType->getTranslation('en')?->name);
        $this->assertSame('English description', $competitionType->getTranslation('en')?->description);
    }

    public function test_varsayilan_dilde_ad_ve_aciklama_zorunludur(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->post(route('eys.competition-types.store'), [
            'code' => 'standard',
            'status' => '1',
            'tr' => ['name' => '', 'description' => ''],
        ]);

        $response->assertSessionHasErrors(['tr.name', 'tr.description']);
    }

    public function test_yarisma_turu_guncellenebilir(): void
    {
        $competitionType = CompetitionType::factory()->create(['code' => 'old-code']);

        $response = $this->actingAs($this->admin(), 'eys')->patch(route('eys.competition-types.update', $competitionType), [
            'code' => 'new-code',
            'status' => '0',
            'sort_order' => '99',
            'tr' => ['name' => 'Güncel Tür', 'description' => 'Güncel açıklama'],
            'en' => ['name' => 'Updated Type', 'description' => 'Updated description'],
        ]);

        $response->assertRedirect(route('eys.competition-types.index'));

        $competitionType->refresh();
        $this->assertSame('new-code', $competitionType->code);
        $this->assertFalse($competitionType->status);
        $this->assertSame(99, $competitionType->sort_order);
        $this->assertSame('Güncel Tür', $competitionType->getTranslation('tr')?->name);
    }

    public function test_yarisma_turu_silinebilir(): void
    {
        $competitionType = CompetitionType::factory()->create();

        $response = $this->actingAs($this->admin(), 'eys')->delete(route('eys.competition-types.destroy', $competitionType));

        $response->assertRedirect(route('eys.competition-types.index'));
        $this->assertSoftDeleted($competitionType);
    }

    public function test_izinsiz_kullanici_yarisma_turleri_sayfasina_erisemez(): void
    {
        $response = $this->actingAs(EysUser::factory()->create(), 'eys')->get(route('eys.competition-types.index'));

        $response->assertForbidden();
    }
}
