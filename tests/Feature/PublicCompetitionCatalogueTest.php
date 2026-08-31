<?php

namespace Tests\Feature;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionPublicationState;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Services\CompetitionPublicSlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCompetitionCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_only_lists_approved_and_published_competitions(): void
    {
        $visible = $this->publishedCompetition('Yayındaki Yarışma');
        $draft = Competition::factory()->withTranslations(['tr' => ['name' => 'Taslak Yarışma']])->create();
        $unpublished = Competition::factory()->withTranslations(['tr' => ['name' => 'Yayınlanmamış Yarışma']])->create();
        $unpublished->forceFill(['status' => CompetitionStatus::Approved])->save();

        $response = $this->get(route('public.home'));

        $response->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($draft->name)
            ->assertDontSee($unpublished->name);
    }

    public function test_catalogue_can_be_filtered_by_audience_and_search_term(): void
    {
        $national = $this->publishedCompetition('Anadolu Ulusal Yarışması', CompetitionAudience::National);
        $international = $this->publishedCompetition('World Photography Award', CompetitionAudience::International);

        $this->get(route('public.competitions.index', ['audience' => 'national']))
            ->assertOk()
            ->assertSee($national->name)
            ->assertDontSee($international->name);

        $this->get(route('public.competitions.index', ['q' => 'World']))
            ->assertOk()
            ->assertSee($international->name)
            ->assertDontSee($national->name);
    }

    public function test_public_detail_uses_stable_slug_and_rejects_unpublished_competitions(): void
    {
        $visible = $this->publishedCompetition('Kent ve İnsan Fotoğraf Yarışması');
        $hidden = Competition::factory()->create();
        app(CompetitionPublicSlugService::class)->ensure($hidden);

        $this->get(route('public.competitions.show', $visible->public_slug))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertSee(__('public.detail.schedule'));

        $this->get(route('public.competitions.show', $hidden->public_slug))->assertNotFound();
    }

    public function test_sitemap_only_includes_public_competition_urls(): void
    {
        $visible = $this->publishedCompetition('Sitemap Yarışması');
        $hidden = Competition::factory()->create();
        app(CompetitionPublicSlugService::class)->ensure($hidden);

        $this->get(route('public.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.competitions.show', $visible), escape: false)
            ->assertDontSee(route('public.competitions.show', $hidden), escape: false);
    }

    private function publishedCompetition(string $name, CompetitionAudience $audience = CompetitionAudience::National): Competition
    {
        $competition = Competition::factory()->create(['audience' => $audience]);
        $translations = ['tr' => ['name' => $name, 'subject' => 'Yarışmanın konusu.', 'purpose' => 'Yarışmanın amacı.']];
        if ($audience === CompetitionAudience::International) {
            $translations['en'] = ['name' => $name, 'subject' => 'Competition subject.', 'purpose' => 'Competition purpose.'];
        }
        $competition->upsertTranslations($translations);
        $competition->forceFill(['status' => CompetitionStatus::Approved, 'published_at' => now(), 'publication_state' => CompetitionPublicationState::Published])->save();
        app(CompetitionPublicSlugService::class)->ensure($competition);

        return $competition->fresh(['translations', 'institution', 'competitionType.translations']);
    }
}
