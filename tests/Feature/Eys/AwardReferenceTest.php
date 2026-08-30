<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\AwardReference;
use App\Models\EysUser;
use App\Models\Permission;
use Database\Seeders\AwardReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AwardReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.award_references.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.award_references.manage');

        return $user;
    }

    public function test_legacy_odul_referanslari_tr_ve_en_karsiliklariyla_seed_edilir(): void
    {
        $this->seed(AwardReferenceSeeder::class);

        $this->assertDatabaseCount('award_references', 61);
        $this->assertDatabaseCount('award_reference_translations', 122);
        $this->assertSame('Büyük Ödül', AwardReference::findOrFail('64819f86-6caf-4ff2-9d97-abe19cf7dccb')->getTranslation('tr', false)?->name);
        $this->assertSame('exhibition', AwardReference::findOrFail('600ebdd5-d97b-4d85-85c0-b3e7026418a8')->kind);
        $this->assertSame('purchase', AwardReference::findOrFail('0e6bff88-a1ba-4701-a570-ec98c7a34280')->kind);
    }

    public function test_odul_referansi_listesi_goruntulenebilir(): void
    {
        $this->seed(AwardReferenceSeeder::class);

        $this->withSession(['locale' => 'tr'])->actingAs($this->admin(), 'eys')
            ->get(route('eys.award-references.index'))
            ->assertOk()
            ->assertSee(__('eys.nav.award_references'))
            ->assertSee('Büyük Ödül');
    }

    public function test_yeni_odul_referansi_tr_ve_en_bilgileriyle_olusturulabilir(): void
    {
        $response = $this->actingAs($this->admin(), 'eys')->post(route('eys.award-references.store'), [
            'code' => 'jury-special-prize',
            'kind' => 'award',
            'sort_order' => 450,
            'status' => '1',
            'tr' => ['name' => 'Jüri Özel Ödülü', 'description' => 'Jürinin özel seçimi.'],
            'en' => ['name' => 'Jury Special Prize', 'description' => 'Special jury selection.'],
        ]);

        $response->assertRedirect(route('eys.award-references.index'));
        $award = AwardReference::where('code', 'jury-special-prize')->firstOrFail();
        $this->assertSame('Jüri Özel Ödülü', $award->getTranslation('tr', false)?->name);
        $this->assertSame('Jury Special Prize', $award->getTranslation('en', false)?->name);
    }

    public function test_sistem_odul_referansi_silinemez(): void
    {
        $this->seed(AwardReferenceSeeder::class);
        $award = AwardReference::firstOrFail();

        $this->actingAs($this->admin(), 'eys')
            ->delete(route('eys.award-references.destroy', $award))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($award);
    }

    public function test_sistem_odul_referansinda_silme_aksiyonu_gosterilmez(): void
    {
        $this->seed(AwardReferenceSeeder::class);
        $award = AwardReference::query()->where('is_system', true)->firstOrFail();

        $this->actingAs($this->admin(), 'eys')
            ->get(route('eys.award-references.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('eys.award-references.destroy', $award).'"', false);
    }

    public function test_izinsiz_kullanici_odul_referanslarina_erisemez(): void
    {
        $this->actingAs(EysUser::factory()->create(), 'eys')
            ->get(route('eys.award-references.index'))
            ->assertForbidden();
    }
}
