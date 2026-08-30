<?php

namespace Tests\Feature\Juri;

use App\Enums\CompetitionStatus;
use App\Models\AwardReference;
use App\Models\Competition;
use App\Models\EvaluationCriterion;
use App\Models\Juri;
use Database\Seeders\AwardReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilgiler_eksikse_uyari_gosterilir(): void
    {
        $juri = Juri::factory()->create(['first_name' => null, 'last_name' => null]);

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertSee(__('juri.dashboard.incomplete_title'));
    }

    public function test_bilgiler_tamamsa_uyari_gosterilmez(): void
    {
        $juri = Juri::factory()->create(['first_name' => 'Ayşe', 'last_name' => 'Kaya']);

        $response = $this->actingAs($juri, 'juri')->get(route('juri.dashboard'));

        $response->assertDontSee(__('juri.dashboard.incomplete_title'));
    }

    public function test_dashboard_atanmis_yarismalari_ve_kategorileri_ozetler(): void
    {
        $juri = Juri::factory()->create(['first_name' => 'Ayşe', 'last_name' => 'Kaya']);
        $competition = Competition::factory()
            ->withTranslations(['tr' => ['name' => 'Ulusal Fotoğraf Yarışması', 'subject' => 'Konu', 'purpose' => 'Amaç']])
            ->create(['status' => CompetitionStatus::Approved]);
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->upsertTranslations(['tr' => ['name' => 'Siyah Beyaz']]);
        $category->jurorAssignments()->create(['juror_id' => $juri->id, 'sort_order' => 10]);

        $this->actingAs($juri, 'juri')->get(route('juri.dashboard'))
            ->assertOk()
            ->assertSee(__('juri.dashboard.recent_assignments'))
            ->assertSee('Ulusal Fotoğraf Yarışması')
            ->assertSee('Siyah Beyaz')
            ->assertSee(route('juri.assignments.index'));
    }

    public function test_gorevlerim_sadece_juriye_atanmis_kategorileri_gosterir(): void
    {
        $juri = Juri::factory()->create();
        $otherJuror = Juri::factory()->create();
        $competition = Competition::factory()
            ->withTranslations(['tr' => ['name' => 'Doğa Yarışması', 'subject' => 'Konu', 'purpose' => 'Amaç']])
            ->create(['status' => CompetitionStatus::UnderReview]);
        $assignedCategory = $competition->categories()->create(['sort_order' => 10]);
        $assignedCategory->upsertTranslations(['tr' => ['name' => 'Doğa']]);
        $assignedCategory->jurorAssignments()->create(['juror_id' => $juri->id, 'sort_order' => 10]);
        $otherCategory = $competition->categories()->create(['sort_order' => 20]);
        $otherCategory->upsertTranslations(['tr' => ['name' => 'Portre']]);
        $otherCategory->jurorAssignments()->create(['juror_id' => $otherJuror->id, 'sort_order' => 10]);

        $this->actingAs($juri, 'juri')->get(route('juri.assignments.index'))
            ->assertOk()
            ->assertSee(__('juri.assignments.title'))
            ->assertSee('Doğa Yarışması')
            ->assertSee('Doğa')
            ->assertDontSee('Portre')
            ->assertSee(route('juri.assignments.show', $competition))
            ->assertSee(__('juri.assignments.status.under_review'));
    }

    public function test_juri_gorev_detayinda_yalnizca_atandigi_kategoriyi_odullerini_ve_sartnameyi_gorur(): void
    {
        $this->seed(AwardReferenceSeeder::class);

        $juri = Juri::factory()->create();
        $otherJuror = Juri::factory()->create();
        $competition = Competition::factory()
            ->withTranslations(['tr' => [
                'name' => 'Belgesel Fotoğraf Yarışması',
                'subject' => 'Toplumsal hafıza',
                'purpose' => 'Belgesel fotoğraf üretimini desteklemek',
            ]])
            ->create(['status' => CompetitionStatus::Submitted]);

        $assignedCategory = $competition->categories()->create(['sort_order' => 10]);
        $assignedCategory->upsertTranslations(['tr' => ['name' => 'Belgesel']]);
        $assignedCategory->jurorAssignments()->create(['juror_id' => $juri->id, 'sort_order' => 10]);
        $criterion = EvaluationCriterion::create([
            'code' => 'visual-impact', 'default_min_score' => 0, 'default_max_score' => 10,
            'default_weight' => 1, 'sort_order' => 10, 'status' => true,
        ]);
        $criterion->upsertTranslations(['tr' => ['name' => 'Görsel Etki', 'description' => 'Eserin izleyicide bıraktığı etki.']]);
        $assignedCategory->evaluationCriteria()->create([
            'evaluation_criterion_id' => $criterion->id,
            'min_score' => 0,
            'max_score' => 20,
            'weight' => 1.5,
            'sort_order' => 10,
        ]);

        $otherCategory = $competition->categories()->create(['sort_order' => 20]);
        $otherCategory->upsertTranslations(['tr' => ['name' => 'Gizli Portre Kategorisi']]);
        $otherCategory->jurorAssignments()->create(['juror_id' => $otherJuror->id, 'sort_order' => 10]);

        [$assignedReference, $otherReference] = AwardReference::query()->ordered()->take(2)->get()->all();
        $assignedAward = $assignedCategory->awards()->create([
            'award_reference_id' => $assignedReference->id,
            'quantity' => 2,
            'sort_order' => 10,
        ]);
        $assignedAward->upsertTranslations(['tr' => ['material_award' => 'Fotoğraf ekipmanı desteği']]);
        $otherCategory->awards()->create([
            'award_reference_id' => $otherReference->id,
            'quantity' => 1,
            'sort_order' => 10,
        ]);

        $competition->regulationSnapshots()->create([
            'version' => 1,
            'content' => [
                'tr' => [[
                    'title' => 'Katılım Koşulları',
                    'items' => [['content' => 'Bu yarışma ulusal katılıma açıktır.']],
                ]],
            ],
            'compiled_at' => now(),
        ]);

        $this->actingAs($juri, 'juri')->get(route('juri.assignments.show', $competition))
            ->assertOk()
            ->assertSee('Belgesel Fotoğraf Yarışması')
            ->assertSee('Toplumsal hafıza')
            ->assertSee('Belgesel')
            ->assertSee($assignedReference->name)
            ->assertSee('Fotoğraf ekipmanı desteği')
            ->assertSee('Görsel Etki')
            ->assertSee(__('juri.assignments.score_range'))
            ->assertSee('0–20')
            ->assertSee('Katılım Koşulları')
            ->assertSee('Bu yarışma ulusal katılıma açıktır.')
            ->assertDontSee('Gizli Portre Kategorisi')
            ->assertDontSee($otherReference->name);
    }

    public function test_juri_atanmamis_yarismanin_gorev_detayini_goremez(): void
    {
        $juri = Juri::factory()->create();
        $otherJuror = Juri::factory()->create();
        $competition = Competition::factory()->create();
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->jurorAssignments()->create(['juror_id' => $otherJuror->id, 'sort_order' => 10]);

        $this->actingAs($juri, 'juri')->get(route('juri.assignments.show', $competition))
            ->assertNotFound();
    }

    public function test_taslak_yarismada_sartname_henuz_yayinlanmadi_bos_durumu_gosterilir(): void
    {
        $juri = Juri::factory()->create();
        $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->jurorAssignments()->create(['juror_id' => $juri->id, 'sort_order' => 10]);

        $this->actingAs($juri, 'juri')->get(route('juri.assignments.show', $competition))
            ->assertOk()
            ->assertSee(__('juri.assignments.regulation_unavailable_title'))
            ->assertSee(__('juri.assignments.regulation_unavailable_text'));
    }

    public function test_gorevi_olmayan_juriye_aciklayici_bos_durum_gosterilir(): void
    {
        $juri = Juri::factory()->create();

        $this->actingAs($juri, 'juri')->get(route('juri.assignments.index'))
            ->assertOk()
            ->assertSee(__('juri.assignments.empty_title'))
            ->assertSee(__('juri.assignments.empty_text'));
    }
}
