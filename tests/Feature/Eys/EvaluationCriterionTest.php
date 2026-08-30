<?php

namespace Tests\Feature\Eys;

use App\Enums\Module;
use App\Models\EvaluationCriterion;
use App\Models\EysUser;
use App\Models\Permission;
use Database\Seeders\EvaluationCriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EvaluationCriterionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): EysUser
    {
        $user = EysUser::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(Module::Eys->value);
        Permission::firstOrCreate(['name' => 'eys.evaluation_criteria.manage', 'guard_name' => 'eys']);
        $user->givePermissionTo('eys.evaluation_criteria.manage');

        return $user;
    }

    public function test_varsayilan_genel_degerlendirme_kriteri_3_9_araligiyla_seed_edilir(): void
    {
        $this->seed(EvaluationCriterionSeeder::class);

        $this->assertDatabaseCount('evaluation_criteria', 1);
        $this->assertDatabaseCount('evaluation_criterion_translations', 2);
        $criterion = EvaluationCriterion::where('code', 'general-evaluation')->firstOrFail();
        $this->assertSame(3, $criterion->default_min_score);
        $this->assertSame(9, $criterion->default_max_score);
        $this->assertSame('Genel Değerlendirme', $criterion->getTranslation('tr', false)?->name);
        $this->assertSame('General Evaluation', $criterion->getTranslation('en', false)?->name);
    }

    public function test_kriter_listesi_goruntulenebilir(): void
    {
        $this->seed(EvaluationCriterionSeeder::class);

        $this->withSession(['locale' => 'tr'])->actingAs($this->admin(), 'eys')
            ->get(route('eys.evaluation-criteria.index'))
            ->assertOk()
            ->assertSee(__('eys.nav.evaluation_criteria'))
            ->assertSee('Genel Değerlendirme')
            ->assertSee('3–9');
    }

    public function test_yeni_kriter_puan_araligi_agirligi_ve_cevirileriyle_olusturulabilir(): void
    {
        $this->actingAs($this->admin(), 'eys')->post(route('eys.evaluation-criteria.store'), [
            'code' => 'storytelling',
            'default_min_score' => 1,
            'default_max_score' => 20,
            'default_weight' => 1.5,
            'sort_order' => 60,
            'status' => '1',
            'tr' => ['name' => 'Görsel Anlatı', 'description' => 'Hikâye kurma gücü.'],
            'en' => ['name' => 'Visual Storytelling', 'description' => 'Strength of visual narrative.'],
        ])->assertRedirect(route('eys.evaluation-criteria.index'));

        $criterion = EvaluationCriterion::where('code', 'storytelling')->firstOrFail();
        $this->assertSame(1, $criterion->default_min_score);
        $this->assertSame(20, $criterion->default_max_score);
        $this->assertSame('1.50', $criterion->default_weight);
        $this->assertSame('Visual Storytelling', $criterion->getTranslation('en', false)?->name);
    }

    public function test_en_yuksek_puan_en_dusuk_puandan_buyuk_olmalidir(): void
    {
        $this->actingAs($this->admin(), 'eys')->post(route('eys.evaluation-criteria.store'), [
            'code' => 'invalid-range',
            'default_min_score' => 10,
            'default_max_score' => 10,
            'default_weight' => 1,
            'status' => '1',
            'tr' => ['name' => 'Geçersiz', 'description' => ''],
            'en' => ['name' => 'Invalid', 'description' => ''],
        ])->assertSessionHasErrors('default_max_score');

        $this->assertDatabaseMissing('evaluation_criteria', ['code' => 'invalid-range']);
    }

    public function test_sistem_kriteri_silinemez(): void
    {
        $this->seed(EvaluationCriterionSeeder::class);
        $criterion = EvaluationCriterion::firstOrFail();

        $this->actingAs($this->admin(), 'eys')
            ->delete(route('eys.evaluation-criteria.destroy', $criterion))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($criterion);
    }

    public function test_izinsiz_kullanici_kriterlere_erisemez(): void
    {
        $this->actingAs(EysUser::factory()->create(), 'eys')
            ->get(route('eys.evaluation-criteria.index'))
            ->assertForbidden();
    }
}
