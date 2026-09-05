<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\Country;
use App\Models\InstitutionStaff;
use App\Models\User;
use App\Services\InstitutionCompetitionOperations;
use App\Services\SecretariatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesSecretariat;
use Tests\TestCase;

class InstitutionCompetitionOperationsTest extends TestCase
{
    use CreatesSecretariat, RefreshDatabase;

    private function fixture(): array
    {
        $f = $this->secretariatFixture();
        $f['competition']->upsertTranslations(['tr' => ['name' => 'Operasyon Yarışması'], 'en' => ['name' => 'Operations Competition']]);
        $f['submission']->category->upsertTranslations(['tr' => ['name' => 'Doğa'], 'en' => ['name' => 'Nature']]);
        $f['submission']->entry->update(['status' => 'approved', 'submitted_at' => now()]);
        $f['submission']->update(['status' => 'approved', 'submitted_at' => now()]);
        $second = $f['competition']->categories()->create(['sort_order' => 20]);
        $second->upsertTranslations(['tr' => ['name' => 'Yaşam'], 'en' => ['name' => 'Life']]);
        $f['submission']->entry->submissions()->create(['competition_category_id' => $second->id, 'status' => 'rejected', 'submitted_at' => now()]);
        $empty = $f['competition']->categories()->create(['sort_order' => 30]);
        $empty->upsertTranslations(['tr' => ['name' => 'Boş kategori']]);
        foreach ([null, now()] as $withdrawn) {
            $f['submission']->photos()->create(['disk_path' => 'synthetic/private.jpg', 'original_filename' => 'private-name.jpg', 'mime_type' => 'image/jpeg', 'file_size_bytes' => 10, 'width' => 10, 'height' => 10, 'sha256' => hash('sha256', $withdrawn ? 'withdrawn' : 'active'), 'withdrawn_at' => $withdrawn]);
        }

        return $f + compact('second', 'empty');
    }

    public function test_secretariat_sees_only_assignments_and_cannot_open_foreign_detail(): void
    {
        $f = $this->fixture();
        $other = Competition::factory()->create();
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.operations.index'))->assertOk()->assertViewHas('competitions', fn ($rows) => $rows->total() === 1);
        $this->get(route('institution.operations.show', $other))->assertNotFound();
        $this->get(route('institution.operations.show', $f['competition']))->assertOk();
        app(SecretariatService::class)->assign($f['competition'], $f['manager'], null, 1, 'Revoke operations access.');
        $this->get(route('institution.operations.show', $f['competition']))->assertNotFound();
    }

    public function test_institution_staff_scope_and_inactive_institution_are_enforced(): void
    {
        $f = $this->fixture();
        $other = InstitutionStaff::factory()->create();
        $this->actingAs($other, 'institution')->get(route('institution.operations.show', $f['competition']))->assertNotFound();
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', $f['competition']))->assertOk();
        $f['competition']->institution->update(['status' => false]);
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.operations.show', $f['competition']))->assertNotFound();
    }

    public function test_counts_distinguish_people_categories_and_withdrawn_photos(): void
    {
        $f = $this->fixture();
        $stats = app(InstitutionCompetitionOperations::class)->statistics($f['competition'], []);
        $this->assertSame(1, $stats['participants']);
        $this->assertSame(2, $stats['submissions']);
        $this->assertSame(1, $stats['photos']);
        $this->assertSame(0, $stats['categories']->firstWhere('id', $f['empty']->id)['photos']);
        $this->assertSame(1, $stats['statuses']['approved']);
        $this->assertSame(1, $stats['statuses']['rejected']);
        $this->actingAs($f['secretariat'], 'institution')->get(route('institution.operations.show', $f['competition']))->assertOk()->assertViewHas('participants', fn ($rows) => $rows->total() === 1 && $rows->first()->submissions->count() === 2);
    }

    public function test_filters_apply_to_both_participant_rows_and_statistics(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', [$f['competition'], 'category' => $f['second']->id, 'status' => 'rejected']))->assertOk()
            ->assertViewHas('statistics', fn ($s) => $s['participants'] === 1 && $s['submissions'] === 1 && $s['photos'] === 0)
            ->assertViewHas('participants', fn ($rows) => $rows->first()->submissions->count() === 1);
        $this->get(route('institution.operations.show', [$f['competition'], 'status' => 'pending_approval']))->assertOk()->assertViewHas('participants', fn ($rows) => $rows->isEmpty());
    }

    public function test_drafts_are_not_counted_and_submitted_withdrawals_keep_their_status(): void
    {
        $f = $this->fixture();
        $draft = CompetitionEntry::create(['competition_id' => $f['competition']->id, 'user_id' => User::factory()->create()->id, 'status' => 'draft']);
        $draft->submissions()->create(['competition_category_id' => $f['second']->id, 'status' => 'draft']);
        $f['submission']->update(['status' => 'withdrawn']);
        $f['submission']->entry->update(['status' => 'withdrawn']);
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', $f['competition']))->assertOk()
            ->assertViewHas('statistics', fn ($s) => $s['participants'] === 1 && $s['submissions'] === 2 && $s['statuses']['withdrawn'] === 1);
    }

    public function test_foreign_category_invalid_status_and_page_are_rejected(): void
    {
        $f = $this->fixture();
        $foreign = Competition::factory()->create()->categories()->create(['sort_order' => 1]);
        $this->actingAs($f['staff'], 'institution')->getJson(route('institution.operations.show', [$f['competition'], 'category' => $foreign->id]))->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->getJson(route('institution.operations.show', [$f['competition'], 'status' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('institution.operations.index', ['page' => -1]))->assertUnprocessable();
    }

    public function test_pagination_does_not_change_totals_or_load_sensitive_profile_fields(): void
    {
        $f = $this->fixture();
        for ($i = 0; $i < 26; $i++) {
            $entry = CompetitionEntry::create(['competition_id' => $f['competition']->id, 'user_id' => User::factory()->create()->id, 'status' => 'approved', 'submitted_at' => now()]);
            $entry->submissions()->create(['competition_category_id' => $f['second']->id, 'status' => 'approved', 'submitted_at' => now()]);
        }
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', [$f['competition'], 'page' => 2]))->assertOk()
            ->assertViewHas('statistics', fn ($s) => $s['participants'] === 27 && $s['submissions'] === 28)
            ->assertViewHas('participants', fn ($rows) => $rows->count() === 2 && ! array_key_exists('email', $rows->first()->user->getAttributes()) && ! array_key_exists('tckimlikno', $rows->first()->user->getAttributes()));
    }

    public function test_results_and_cancelled_filters_do_not_treat_application_deadline_as_archive(): void
    {
        $f = $this->fixture();
        $f['competition']->update(['application_ends_at' => now()->subDay()]);
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.index', ['state' => 'ongoing']))->assertOk()->assertViewHas('competitions', fn ($rows) => $rows->total() === 1);
        $f['competition']->forceFill(['results_published_at' => now()])->save();
        $this->get(route('institution.operations.index', ['state' => 'results']))->assertOk()->assertViewHas('competitions', fn ($rows) => $rows->total() === 1);
        $this->get(route('institution.operations.index', ['state' => 'ongoing']))->assertOk()->assertViewHas('competitions', fn ($rows) => $rows->isEmpty());
    }

    public function test_profile_text_is_escaped_and_private_file_paths_are_absent(): void
    {
        $f = $this->fixture();
        $f['member']->update(['first_name' => '<script>alert(1)</script>']);
        $response = $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', $f['competition']))->assertOk();
        $response->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('synthetic/private.jpg')->assertDontSee('private-name.jpg')->assertDontSee($f['member']->email);
        $this->get(route('institution.language', 'en'));
        $this->get(route('institution.operations.show', $f['competition']))->assertOk()->assertSee('Operations Competition')->assertSee('Entry summary');
    }

    public function test_deleted_location_references_still_render_their_names_without_duplicate_counts(): void
    {
        $f = $this->fixture();
        $country = Country::create(['status' => true]);
        $country->upsertTranslations(['tr' => ['short_name' => 'Türkiye', 'official_name' => 'Türkiye'], 'en' => ['short_name' => 'Türkiye', 'official_name' => 'Türkiye']]);
        $city = City::create(['country_id' => $country->id, 'status' => true]);
        $city->upsertTranslations(['tr' => ['official_name' => 'İzmir'], 'en' => ['official_name' => 'İzmir']]);
        $f['member']->update(['country_id' => $country->id, 'city_id' => $city->id]);
        $country->delete();
        $city->delete();
        $this->actingAs($f['staff'], 'institution')->get(route('institution.operations.show', $f['competition']))
            ->assertOk()->assertSee('Türkiye')->assertSee('İzmir')
            ->assertViewHas('statistics', fn ($s) => $s['participants'] === 1 && $s['submissions'] === 2);
    }

    public function test_participant_page_query_count_does_not_grow_per_person(): void
    {
        $f = $this->fixture();
        $load = function () use ($f) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $rows = app(InstitutionCompetitionOperations::class)->participants($f['competition'], [])->paginate(25);
                foreach ($rows as $row) {
                    foreach ($row->submissions as $submission) {
                        $submission->category->name;
                    }
                    $row->user?->country?->short_name;
                    $row->user?->city?->official_name;
                }

                return count(DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
            }
        };
        $one = $load();
        for ($i = 0; $i < 24; $i++) {
            $entry = CompetitionEntry::create(['competition_id' => $f['competition']->id, 'user_id' => User::factory()->create()->id, 'status' => 'approved', 'submitted_at' => now()]);
            $entry->submissions()->create(['competition_category_id' => $f['second']->id, 'status' => 'approved', 'submitted_at' => now()]);
        }
        $this->assertSame($one, $load());
    }
}
