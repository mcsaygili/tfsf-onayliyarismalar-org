<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use Database\Seeders\CompetitionCategoryReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCategoryStepTest extends TestCase
{
    use RefreshDatabase;

    private function context(CompetitionAudience $audience = CompetitionAudience::National): array
    {
        $this->seed(CompetitionCategoryReferenceSeeder::class);
        $institution = Institution::factory()->create();
        $staff = InstitutionStaff::factory()->for($institution)->create();
        $competition = Competition::factory()->for($institution)->for($staff)->create(['audience' => $audience, 'current_step' => 6]);

        return [$staff, $competition];
    }

    private function categoryPayload(bool $english = false): array
    {
        return [
            'tr' => ['name' => 'Siyah-Beyaz'],
            'en' => ['name' => $english ? 'Monochrome' : ''],
            'age_eligibility_rule' => AgeEligibilityRule::where('code', 'no-age-check')->firstOrFail()->id,
            'gender_id' => ParticipantGender::where('code', 'no-check')->firstOrFail()->id,
            'member_group_ids' => [MemberGroup::firstOrFail()->id],
            'capture_device_ids' => [CaptureDevice::firstOrFail()->id],
            'processing_method_ids' => [ProcessingMethod::where('code', 'no-processing-check')->firstOrFail()->id],
            'member_group_match_mode' => 'any',
        ];
    }

    public function test_adim_6_kategori_katilimci_ve_cihaz_bilgilerini_gosterir(): void
    {
        [$staff, $competition] = $this->context();
        $response = $this->withSession(['locale' => 'tr'])->actingAs($staff, 'institution')->get(route('institution.competitions.step.show', [$competition, 6]));

        $response->assertOk()
            ->assertSee(__('institution.competitions.category_information_title'))
            ->assertSee(__('institution.competitions.participant_information_title'))
            ->assertSee(__('institution.competitions.device_information_title'))
            ->assertSee('Fotoğraf Makinesi')
            ->assertSee('Fotoğraf Düzenleme Uygulaması')
            ->assertSee('Cinsiyet Kontrolü Yok')
            ->assertSee('18 Yaş Altı Katılımcı')
            ->assertSee('x-text="categories.length"', false)
            ->assertSee('@click="category.locale = \'en\'"', false)
            ->assertSee('x-show="category.locale === \'tr\'"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee('ip-field-help-button', false);
    }

    public function test_en_az_bir_kategori_zorunludur(): void
    {
        [$staff, $competition] = $this->context();
        $response = $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 6]), ['categories' => [], 'action' => 'next']);
        $response->assertSessionHasErrors('categories');
    }

    public function test_ulusal_yarismada_turkce_kategori_ve_kurallar_kaydedilir(): void
    {
        [$staff, $competition] = $this->context();
        $payload = ['categories' => [$this->categoryPayload()], 'action' => 'next'];
        $response = $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 6]), $payload);

        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 7]));
        $category = $competition->categories()->with(['translations', 'genders', 'memberGroups', 'captureDevices', 'processingMethods'])->firstOrFail();
        $this->assertSame('Siyah-Beyaz', $category->getTranslation('tr', false)?->name);
        $this->assertNull($category->getTranslation('en', false));
        $this->assertCount(1, $category->genders);
        $this->assertCount(1, $category->memberGroups);
        $this->assertCount(1, $category->captureDevices);
        $this->assertCount(1, $category->processingMethods);
    }

    public function test_uluslararasi_yarismada_ingilizce_kategori_adi_zorunludur(): void
    {
        [$staff, $competition] = $this->context(CompetitionAudience::International);
        $response = $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 6]), ['categories' => [$this->categoryPayload()], 'action' => 'next']);
        $response->assertSessionHasErrors('categories.0.en.name');
    }

    public function test_yas_kurali_yarisma_sonlanma_tarihi_aciklamasiyla_kaydedilir(): void
    {
        [$staff, $competition] = $this->context();
        $category = $this->categoryPayload();
        $ageRule = AgeEligibilityRule::where('code', 'under-18')->firstOrFail();
        $category['age_eligibility_rule'] = $ageRule->id;
        $response = $this->actingAs($staff, 'institution')->put(route('institution.competitions.step.update', [$competition, 6]), ['categories' => [$category], 'action' => 'next']);
        $response->assertRedirect(route('institution.competitions.step.show', [$competition, 7]));
        $this->assertSame($ageRule->id, $competition->categories()->firstOrFail()->age_eligibility_rule_id);
    }
}
