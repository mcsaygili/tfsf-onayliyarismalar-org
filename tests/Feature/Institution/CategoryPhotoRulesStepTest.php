<?php

namespace Tests\Feature\Institution;

use App\Models\Competition;
use App\Models\InstitutionStaff;
use App\Support\CompetitionRegulations\CompetitionRegulationContextBuilder;
use App\Support\CompetitionWizard\Step6;
use App\Support\Photo\CategoryPhotoRules;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryPhotoRulesStepTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $staff = InstitutionStaff::factory()->create();
        $competition = Competition::factory()->create(['institution_id' => $staff->institution_id, 'institution_staff_id' => $staff->id, 'current_step' => 6]);
        $this->actingAs($staff, 'institution');

        return [$competition, route('institution.competitions.step.update', [$competition, 6])];
    }

    public function test_rules_round_trip_clear_and_survive_older_payloads(): void
    {
        [$competition, $url] = $this->context();
        $rules = array_replace(CategoryPhotoRules::defaults(), ['formats' => ['jpeg'], 'min_file_size_mb' => 0.125, 'max_file_size_mb' => 10,
            'min_short_edge' => 1920, 'max_long_edge' => 4000, 'min_dpi' => 300, 'max_dpi' => 300]);
        $payload = ['tr' => ['name' => 'Teknik Koşullar'], 'photo_rules' => $rules];
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $category = $competition->categories()->firstOrFail();
        $this->assertEquals($rules, (new Step6)->data($competition)['categories'][0]['photo_rules']);
        $payload['id'] = $category->id;
        unset($payload['photo_rules']);
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertEquals($rules, $category->fresh()->photo_rules);
        $payload['photo_rules'] = array_replace(CategoryPhotoRules::defaults(), ['min_dpi' => 0]);
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertSame(CategoryPhotoRules::defaults(), $category->fresh()->photo_rules);
    }

    public static function invalidRules(): array
    {
        return [
            'reversed file range' => [['min_file_size_mb' => 5, 'max_file_size_mb' => 4]],
            'reversed edges' => [['min_short_edge' => 3000, 'max_long_edge' => 2000]],
            'reversed dpi' => [['min_dpi' => 301, 'max_dpi' => 300]],
            'negative' => [['min_dpi' => -1]],
            'fractional pixel' => [['min_short_edge' => 1.5]],
            'too precise size' => [['min_file_size_mb' => 0.0001]],
            'over system limit' => [['max_file_size_mb' => 16]],
            'unsupported format' => [['formats' => ['svg']]],
            'empty formats' => [['formats' => []]],
            'unknown configuration key' => [['disable_validation' => true]],
        ];
    }

    #[DataProvider('invalidRules')]
    public function test_invalid_rules_cannot_be_saved_even_as_draft(array $rules): void
    {
        [$competition, $url] = $this->context();
        $this->put($url, ['categories' => [['tr' => ['name' => 'Test'], 'photo_rules' => array_replace(CategoryPhotoRules::defaults(), $rules)]], 'action' => 'draft'])->assertSessionHasErrors();
        $this->assertSame(0, $competition->categories()->count());
    }

    public function test_missing_all_formats_is_rejected_and_other_institution_cannot_write(): void
    {
        [$competition, $url] = $this->context();
        $this->put($url, ['categories' => [['photo_rules' => ['min_dpi' => 72]]], 'action' => 'draft'])->assertSessionHasErrors('categories.0.photo_rules.formats');
        $this->actingAs(InstitutionStaff::factory()->create(), 'institution')->put($url, ['categories' => [['photo_rules' => CategoryPhotoRules::defaults()]], 'action' => 'draft'])->assertNotFound();
        $this->assertSame(0, $competition->categories()->count());
    }

    public function test_single_bound_and_zero_are_valid_and_regulation_summary_uses_requested_language(): void
    {
        [$competition, $url] = $this->context();
        $this->put($url, ['categories' => [['tr' => ['name' => 'Test'], 'photo_rules' => ['formats' => ['jpeg'], 'min_dpi' => 300, 'max_dpi' => 0]]], 'action' => 'draft'])->assertSessionHasNoErrors();
        app()->setLocale('tr');
        $context = app(CompetitionRegulationContextBuilder::class)->build($competition, 'en');
        $this->assertStringContainsString('Both axes ≥ 300 DPI', $context['categories'][0]['photo_rules']);
        $this->assertStringNotContainsString('Her iki', $context['categories'][0]['photo_rules']);
    }

    public function test_story_and_order_flags_save_preserve_and_clear_without_silent_reset(): void
    {
        [$competition, $url] = $this->context();
        $payload = ['tr' => ['name' => 'Anlatı'], 'photo_story_required' => true, 'category_story_required' => true, 'photo_order_required' => true];
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $category = $competition->categories()->sole();
        foreach (SubmissionDeclarations::CATEGORY_FLAGS as $flag) {
            $this->assertTrue($category->{$flag});
        }
        $this->put($url, ['categories' => [['id' => $category->id, 'tr' => ['name' => 'Anlatı']]], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertTrue($category->fresh()->category_story_required);
        $payload['id'] = $category->id;
        foreach (SubmissionDeclarations::CATEGORY_FLAGS as $flag) {
            $payload[$flag] = '0';
        }
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertFalse($category->fresh()->category_story_required);
        $payload['photo_story_required'] = 'false-but-truthy';
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasErrors('categories.0.photo_story_required');
    }

    public function test_series_setting_round_trips_preserves_older_forms_and_is_in_regulation(): void
    {
        [$competition, $url] = $this->context();
        $payload = ['tr' => ['name' => 'Series'], 'photos_grouped' => true];
        $this->put($url, ['categories' => [$payload], 'action' => 'draft'])->assertSessionHasNoErrors();
        $category = $competition->categories()->sole();
        $this->assertTrue($category->photos_grouped);
        $this->assertTrue((new Step6)->data($competition)['categories'][0]['photos_grouped']);
        $this->put($url, ['categories' => [['id' => $category->id, 'tr' => ['name' => 'Series']]], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertTrue($category->fresh()->photos_grouped);
        $context = app(CompetitionRegulationContextBuilder::class)->build($competition->fresh(), 'en');
        $this->assertTrue($context['categories'][0]['photos_grouped']);
        $this->assertStringContainsString('ordered photo series', $context['categories'][0]['declarations']);
        $this->put($url, ['categories' => [['id' => $category->id, 'photos_grouped' => false]], 'action' => 'draft'])->assertSessionHasNoErrors();
        $this->assertFalse($category->fresh()->photos_grouped);
        $this->put($url, ['categories' => [['id' => $category->id, 'photos_grouped' => 'not-a-boolean']], 'action' => 'draft'])->assertSessionHasErrors('categories.0.photos_grouped');
    }
}
