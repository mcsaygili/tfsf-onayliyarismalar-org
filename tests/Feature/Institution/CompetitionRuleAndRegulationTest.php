<?php

namespace Tests\Feature\Institution;

use App\Enums\CompetitionAudience;
use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use App\Models\RegulationItem;
use App\Support\CompetitionRegulations\CompetitionRegulationCompiler;
use App\Support\CompetitionRules\CompetitionEligibilityEvaluator;
use Database\Seeders\CompetitionCategoryReferenceSeeder;
use Database\Seeders\RegulationItemSeeder;
use Database\Seeders\RegulationSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionRuleAndRegulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_uygunluk_kurallari_tek_motor_tarafindan_degerlendirilir(): void
    {
        $this->seed(CompetitionCategoryReferenceSeeder::class);
        $competition = Competition::factory()->create(['competition_ends_at' => '2027-12-31 23:59:00']);
        $category = $competition->categories()->create([
            'sort_order' => 10,
            'age_eligibility_rule_id' => AgeEligibilityRule::where('code', 'under-18')->firstOrFail()->id,
            'member_group_match_mode' => 'any',
        ]);
        $gender = ParticipantGender::where('code', 'male')->firstOrFail();
        $group = MemberGroup::where('code', '0')->firstOrFail();
        $device = CaptureDevice::where('code', 'camera')->firstOrFail();
        $processing = ProcessingMethod::where('code', 'photo-editing-software')->firstOrFail();
        $category->genders()->sync([$gender->id]);
        $category->memberGroups()->sync([$group->id]);
        $category->captureDevices()->sync([$device->id]);
        $category->processingMethods()->sync([$processing->id]);

        $evaluator = app(CompetitionEligibilityEvaluator::class);
        $eligible = $evaluator->evaluate($category, [
            'birth_date' => '2012-01-01', 'gender_id' => $gender->id,
            'member_group_ids' => [$group->id], 'capture_device_id' => $device->id,
            'processing_method_ids' => [$processing->id],
        ]);
        $ineligible = $evaluator->evaluate($category, [
            'birth_date' => '1990-01-01', 'gender_id' => ParticipantGender::where('code', 'female')->firstOrFail()->id,
            'member_group_ids' => [], 'capture_device_id' => null,
            'processing_method_ids' => [],
        ]);

        $this->assertTrue($eligible['eligible']);
        $this->assertFalse($ineligible['eligible']);
        $this->assertEqualsCanonicalizing(
            ['gender_not_eligible', 'age_not_eligible', 'membership_not_eligible', 'device_not_eligible'],
            $ineligible['violations'],
        );
    }

    public function test_sartname_kaynak_sabit_ve_kurum_girdilerini_derleyip_surumlendirir(): void
    {
        $this->seed([CompetitionCategoryReferenceSeeder::class, RegulationSectionSeeder::class, RegulationItemSeeder::class]);
        $competition = Competition::factory()->create();
        $competition->upsertTranslations(['tr' => [
            'name' => 'Doğa Yarışması', 'subject' => 'Doğa', 'purpose' => 'Farkındalık',
        ]]);
        $inputItem = RegulationItem::where('code', 'institution-special-conditions')->firstOrFail();
        $competition->regulationInputs()->create([
            'regulation_item_id' => $inputItem->id,
            'locale' => 'tr',
            'content' => 'Her katılımcı en fazla dört eserle başvurabilir.',
        ]);

        $compiler = app(CompetitionRegulationCompiler::class);
        $compiled = $compiler->compile($competition);
        $content = collect($compiled['tr'])->flatMap(fn (array $section) => $section['items'])->pluck('content');

        $this->assertTrue($content->contains(fn (string $text) => str_contains($text, 'Doğa Yarışması')));
        $this->assertContains('Her katılımcı en fazla dört eserle başvurabilir.', $content);
        $this->assertTrue($content->contains(fn (string $text) => str_contains($text, 'kullanım haklarını')));

        $first = $compiler->snapshot($competition);
        $second = $compiler->snapshot($competition);
        $this->assertSame(1, $first->version);
        $this->assertSame(2, $second->version);
        $this->assertSame($compiled, $first->content);
    }

    public function test_uluslararasi_sartname_tr_en_uretilir_ve_kategori_maddeleri_tekrarlanir(): void
    {
        $this->seed([CompetitionCategoryReferenceSeeder::class, RegulationSectionSeeder::class, RegulationItemSeeder::class]);
        $competition = Competition::factory()->create(['audience' => CompetitionAudience::International]);
        $competition->upsertTranslations([
            'tr' => ['name' => 'Dünya Fotoğraf Yarışması', 'subject' => 'Kent', 'purpose' => 'Görsel kültürü geliştirmek.'],
            'en' => ['name' => 'World Photography Competition', 'subject' => 'Urban Life', 'purpose' => 'To promote visual culture.'],
        ]);
        $category = $competition->categories()->create([
            'sort_order' => 10,
            'age_eligibility_rule_id' => AgeEligibilityRule::where('code', 'no-age-check')->firstOrFail()->id,
            'member_group_match_mode' => 'any',
        ]);
        $category->upsertTranslations(['tr' => ['name' => 'Renkli'], 'en' => ['name' => 'Colour']]);
        $category->genders()->sync([ParticipantGender::where('code', 'no-check')->firstOrFail()->id]);

        $compiled = app(CompetitionRegulationCompiler::class)->compile($competition);
        $trItems = collect($compiled['tr'])->flatMap(fn (array $section) => $section['items']);
        $enItems = collect($compiled['en'])->flatMap(fn (array $section) => $section['items']);

        $this->assertArrayHasKey('en', $compiled);
        $this->assertTrue($trItems->contains(fn (array $item) => $item['code'] === 'audience-international' && str_contains($item['content'], 'kimlik doğrulaması yapılmayacaktır')));
        $this->assertTrue($enItems->contains(fn (array $item) => $item['code'] === 'audience-international' && str_contains($item['content'], 'No Turkish identity number')));
        $this->assertTrue($enItems->contains(fn (array $item) => $item['code'] === 'category-definition' && str_contains($item['content'], 'Colour')));
    }

    public function test_adim_9_dinamik_sartnameyi_salt_okunur_gosterir(): void
    {
        $this->seed([CompetitionCategoryReferenceSeeder::class, RegulationSectionSeeder::class, RegulationItemSeeder::class]);
        $competition = Competition::factory()->create(['current_step' => 9]);
        $competition->upsertTranslations(['tr' => [
            'name' => 'Anadolu Fotoğraf Yarışması', 'subject' => 'Anadolu', 'purpose' => 'Fotoğraf sanatını desteklemek.',
        ]]);

        $response = $this->actingAs($competition->institutionStaff, 'institution')
            ->get(route('institution.competitions.step.show', [$competition, 9]));

        $response->assertOk()
            ->assertSee(__('institution.competitions.regulation.title'))
            ->assertSee('Anadolu Fotoğraf Yarışması')
            ->assertSee(__('institution.competitions.regulation.ready'))
            ->assertSee('name="regulation_ready" value="1"', false);
    }
}
