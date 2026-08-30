<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
use App\Models\AwardReference;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Database\Seeders\AwardReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionAwardStepTest extends TestCase
{
    use RefreshDatabase;

    private function context(CompetitionAudience $audience = CompetitionAudience::National): array
    {
        $this->seed(AwardReferenceSeeder::class);
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create(['audience' => $audience, 'current_step' => 7]);
        $category = $competition->categories()->create(['sort_order' => 10]);
        $category->upsertTranslations(['tr' => ['name' => 'Siyah-Beyaz'], 'en' => ['name' => 'Monochrome']]);

        return [$staff, $competition, $category, AwardReference::where('code', 'tfsf-gold-medal')->firstOrFail()];
    }

    private function payload(CompetitionCategory $category, AwardReference $award): array
    {
        return ['categories' => [$category->id => ['awards' => [[
            'id' => '',
            'award_reference_id' => $award->id,
            'quantity' => 2,
            'tr' => ['special_award_text' => 'Seçici Kurul Özel Ödülü', 'material_award' => '10.000 TL + Plaket'],
            'en' => ['special_award_text' => 'Jury Special Prize', 'material_award' => 'TRY 10,000 + Plaque'],
        ]]]], 'action' => 'next'];
    }

    public function test_adim_7_kategorileri_ve_legacy_odul_referanslarini_gosterir(): void
    {
        [$staff, $competition] = $this->context();

        $this->withSession(['locale' => 'tr'])->actingAs($staff, 'institution')
            ->get(route('institution.competitions.step.show', [$competition, 7]))
            ->assertOk()
            ->assertSee(__('institution.competitions.category_awards_title'))
            ->assertSee('Siyah-Beyaz')
            ->assertSee('TFSF Altın Madalya')
            ->assertSee('Sergileme')
            ->assertSee('ip-field-help-button', false);
    }

    public function test_her_kategori_icin_en_az_bir_odul_zorunludur(): void
    {
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 7]), [
                'categories' => [$category->id => ['awards' => []]],
                'action' => 'next',
            ])->assertSessionHasErrors("categories.{$category->id}.awards");
    }

    public function test_bos_odul_satiri_taslak_kayitta_veritabanina_yazilmaz(): void
    {
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 7]), [
                'categories' => [$category->id => ['awards' => [[
                    'id' => '',
                    'award_reference_id' => '',
                    'quantity' => 1,
                    'tr' => ['special_award_text' => '', 'material_award' => ''],
                    'en' => ['special_award_text' => '', 'material_award' => ''],
                ]]]],
                'action' => 'draft',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 7]));

        $this->assertDatabaseMissing('competition_category_awards', [
            'competition_category_id' => $category->id,
        ]);
        $this->assertSame(7, $competition->fresh()->current_step);
    }

    public function test_odul_referansi_olmadan_detay_yazilan_taslak_guvenli_bicimde_reddedilir(): void
    {
        [$staff, $competition, $category] = $this->context();

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 7]), [
                'categories' => [$category->id => ['awards' => [[
                    'award_reference_id' => '',
                    'quantity' => 1,
                    'tr' => ['special_award_text' => 'Özel Ödül', 'material_award' => ''],
                    'en' => ['special_award_text' => '', 'material_award' => ''],
                ]]]],
                'action' => 'draft',
            ])
            ->assertSessionHasErrors("categories.{$category->id}.awards.0.award_reference_id");

        $this->assertDatabaseMissing('competition_category_awards', [
            'competition_category_id' => $category->id,
        ]);
    }

    public function test_kategori_odulu_adet_ve_dil_bazli_detaylariyla_kaydedilir(): void
    {
        [$staff, $competition, $category, $awardReference] = $this->context(CompetitionAudience::International);

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 7]), $this->payload($category, $awardReference))
            ->assertRedirect(route('institution.competitions.step.show', [$competition, 8]));

        $award = $category->awards()->with('translations')->firstOrFail();
        $this->assertSame($awardReference->id, $award->award_reference_id);
        $this->assertSame(2, $award->quantity);
        $this->assertSame('Seçici Kurul Özel Ödülü', $award->getTranslation('tr', false)?->special_award_text);
        $this->assertSame('TRY 10,000 + Plaque', $award->getTranslation('en', false)?->material_award);
    }

    public function test_baska_yarismanin_kategori_odulu_guncellenemez(): void
    {
        [$staff, $competition, $category, $awardReference] = $this->context();
        $other = Competition::factory()->create();
        $otherCategory = $other->categories()->create(['sort_order' => 10]);
        $foreignAward = $otherCategory->awards()->create(['award_reference_id' => $awardReference->id, 'quantity' => 1]);
        $payload = $this->payload($category, $awardReference);
        $payload['categories'][$category->id]['awards'][0]['id'] = $foreignAward->id;

        $this->actingAs($staff, 'institution')
            ->put(route('institution.competitions.step.update', [$competition, 7]), $payload)
            ->assertSessionHasErrors("categories.{$category->id}.awards.0.id");
    }
}
