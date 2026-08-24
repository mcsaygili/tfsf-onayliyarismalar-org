<?php

namespace Tests\Feature\Institution;

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

        $this->assertContains('Doğa Yarışması', $content);
        $this->assertContains('Her katılımcı en fazla dört eserle başvurabilir.', $content);
        $this->assertTrue($content->contains(fn (string $text) => str_contains($text, 'kullanım haklarını')));

        $first = $compiler->snapshot($competition);
        $second = $compiler->snapshot($competition);
        $this->assertSame(1, $first->version);
        $this->assertSame(2, $second->version);
        $this->assertSame($compiled, $first->content);
    }
}
