<?php

namespace Tests\Feature\Eys;

use App\Models\Competition;
use App\Models\EysUser;
use App\Services\CompetitionResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesResultSelection;
use Tests\TestCase;

class ResultWorkPreviewTest extends TestCase
{
    use CreatesResultSelection, RefreshDatabase;

    public function test_tied_works_have_distinct_codes_in_award_options_and_keep_them_after_recalculation(): void
    {
        $f = $this->resultFixture();
        DB::table('jury_scores')->where('competition_evaluation_round_id', $f['round']->id)->update(['score' => 8]);
        app(CompetitionResultService::class)->aggregate($f['round']);
        $this->assertEquals($f['result']->refresh()->rank, $f['otherResult']->refresh()->rank);
        $response = $this->get(route('eys.competitions.show', $f['competition']))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $options = $xpath->query('//select[starts-with(@name,"award_assignments[")]/option[@value!=""]');
        $this->assertCount(2, $options);
        $this->assertNotSame($options[0]->textContent, $options[1]->textContent);
        foreach ([$f['photo'], $f['otherPhoto']] as $photo) {
            $code = $photo->workCode();
            $response->assertSee($code);
            $photo->update(['withdrawn_at' => now()]);
            $photo->update(['withdrawn_at' => null]);
            app(CompetitionResultService::class)->aggregate($f['round']);
            $this->assertSame($code, $photo->refresh()->workCode());
        }
        $this->assertNotSame($f['photo']->anonymous_code, $f['otherPhoto']->anonymous_code);
        $response->assertDontSee('private-test.jpg');
    }

    public function test_stable_codes_cannot_be_changed_by_a_model_update(): void
    {
        $f = $this->resultFixture();
        $this->expectException(\LogicException::class);
        $f['photo']->forceFill(['anonymous_code' => '0000000000000000'])->save();
    }

    public function test_final_committee_and_jury_use_the_same_work_reference(): void
    {
        $f = $this->resultFixture();
        $this->post(route('eys.competitions.create-final-round', $f['competition']), ['result_context' => $f['context'], 'photo_result_ids' => [$f['result']->id]])->assertSessionHasNoErrors();
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk()->assertSee($f['photo']->workCode())->assertDontSee('name="photo_result_ids[]"', false);
        $this->actingAs($f['juror'], 'juri');
        $this->get(route('juri.evaluations.show', [$f['competition'], $f['submission']->category]))->assertOk()->assertSee($f['photo']->workCode());
    }

    public function test_authorized_preview_serves_only_safe_copy_and_enforces_competition_scope(): void
    {
        Storage::fake('local');
        $f = $this->resultFixture();
        $f['photo']->update(['jury_path' => 'safe/work.jpg', 'jury_sanitized_at' => now()]);
        Storage::disk('local')->put('safe/work.jpg', 'sanitized-image-bytes');
        Storage::disk('local')->put($f['photo']->disk_path, 'original-private-identity');
        $url = route('eys.competitions.results.photos.show', [$f['competition'], $f['photo']]);
        $this->get($url)->assertOk()->assertStreamedContent('sanitized-image-bytes')->assertHeader('X-Content-Type-Options', 'nosniff');
        $other = Competition::factory()->create();
        $this->get(route('eys.competitions.results.photos.show', [$other, $f['photo']]))->assertNotFound();
        $this->actingAs(EysUser::factory()->create(), 'eys');
        $this->get($url)->assertForbidden();
    }

    public function test_missing_unsafe_withdrawn_and_rejected_work_previews_never_fall_back_to_original(): void
    {
        Storage::fake('local');
        $f = $this->resultFixture();
        Storage::disk('local')->put($f['photo']->disk_path, 'original-private-identity');
        $url = route('eys.competitions.results.photos.show', [$f['competition'], $f['photo']]);
        $f['photo']->update(['jury_sanitized_at' => null]);
        $this->get($url)->assertNotFound();
        $f['photo']->update(['jury_path' => 'safe.jpg', 'jury_sanitized_at' => now()]);
        $this->get($url)->assertNotFound();
        Storage::disk('local')->put('safe.jpg', 'sanitized-image-bytes');
        $f['photo']->update(['withdrawn_at' => now()]);
        $this->get($url)->assertNotFound();
        $f['photo']->update(['withdrawn_at' => null]);
        $f['submission']->update(['status' => 'rejected']);
        $this->get($url)->assertNotFound();
        $f['submission']->update(['status' => 'approved']);
        $f['photo']->update(['jury_sanitized_at' => null]);
        $this->get($url)->assertNotFound();
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk()->assertSee(__('result_selection.unavailable'));
    }

    public function test_uncomputed_round_explains_initial_setup_without_claiming_scores_changed(): void
    {
        $f = $this->resultFixture();
        $f['round']->forceFill(['results_state_hash' => null])->save();
        $this->get(route('eys.competitions.show', $f['competition']))->assertOk()
            ->assertSee(__('result_selection.prepare'))->assertSee(__('result_selection.assign'))->assertDontSee(__('result_selection.recalculate'));
    }
}
