<?php

namespace App\Support\CompetitionRegulations;

use App\Models\Competition;
use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Support\Collection;
use App\Models\CompetitionRegulationSnapshot;

class CompetitionRegulationCompiler
{
    public function snapshot(Competition $competition): CompetitionRegulationSnapshot
    {
        return $competition->regulationSnapshots()->create([
            'version' => ((int) $competition->regulationSnapshots()->max('version')) + 1,
            'content' => $this->compile($competition),
            'compiled_at' => now(),
        ]);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function compile(Competition $competition): array
    {
        $competition->loadMissing([
            'translations', 'institution', 'competitionType.translations',
            'captureRegions.country.translations', 'captureRegions.city',
            'participantApprovalProcess.translations',
            'categories.translations', 'categories.genders.translations',
            'categories.ageEligibilityRule.translations', 'categories.memberGroups.translations',
            'categories.captureDevices.translations', 'categories.processingMethods.translations',
            'regulationInputs',
        ]);

        $locales = $competition->requiresEnglishContent() ? ['tr', 'en'] : ['tr'];
        $sections = RegulationSection::active()->ordered()
            ->with(['translations', 'items' => fn ($query) => $query->active()->ordered()->with('translations')])
            ->get();

        return collect($locales)->mapWithKeys(fn (string $locale) => [
            $locale => $this->compileLocale($competition, $sections, $locale),
        ])->all();
    }

    /**
     * @param Collection<int, RegulationSection> $sections
     * @return array<int, array<string, mixed>>
     */
    private function compileLocale(Competition $competition, Collection $sections, string $locale): array
    {
        return $sections->map(function (RegulationSection $section) use ($competition, $locale) {
            $items = $section->items
                ->filter(fn (RegulationItem $item) => $this->matches($item, $competition))
                ->map(fn (RegulationItem $item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'content_type' => $item->content_type,
                    'content' => $this->itemContent($item, $competition, $locale),
                ])
                ->filter(fn (array $item) => filled($item['content']))
                ->values()
                ->all();

            return [
                'id' => $section->id,
                'code' => $section->code,
                'title' => $section->getTranslation($locale, false)?->name
                    ?? $section->getTranslation(config('locales.default'), false)?->name,
                'items' => $items,
            ];
        })->filter(fn (array $section) => $section['items'] !== [])->values()->all();
    }

    private function matches(RegulationItem $item, Competition $competition): bool
    {
        $conditions = $item->conditions ?? [];

        return (! isset($conditions['audience']) || in_array($competition->audience?->value, (array) $conditions['audience'], true))
            && (! isset($conditions['infrastructure_provider']) || in_array($competition->infrastructure_provider?->value, (array) $conditions['infrastructure_provider'], true))
            && (! isset($conditions['competition_type']) || in_array($competition->competitionType?->code, (array) $conditions['competition_type'], true));
    }

    private function itemContent(RegulationItem $item, Competition $competition, string $locale): ?string
    {
        if ($item->content_type === 'institution_input') {
            return $competition->regulationInputs
                ->first(fn ($input) => $input->regulation_item_id === $item->id && $input->locale === $locale)
                ?->content;
        }

        if ($item->content_type === 'source') {
            return $this->sourceContent($item->source_key, $competition, $locale);
        }

        return $item->getTranslation($locale, false)?->content
            ?? $item->getTranslation(config('locales.default'), false)?->content;
    }

    private function sourceContent(?string $key, Competition $competition, string $locale): ?string
    {
        $translation = $competition->getTranslation($locale, false)
            ?? $competition->getTranslation(config('locales.default'), false);

        return match ($key) {
            'competition.name' => $translation?->name,
            'competition.subject' => $translation?->subject,
            'competition.purpose' => $translation?->purpose,
            'competition.organizer' => $competition->institution?->name,
            'competition.partners' => $competition->partners,
            'competition.schedule' => collect([
                $competition->application_starts_at?->format('d.m.Y H:i'),
                $competition->application_ends_at?->format('d.m.Y H:i'),
                $competition->competition_ends_at?->format('d.m.Y H:i'),
            ])->filter()->join(' — '),
            'competition.categories' => $competition->categories->map(function ($category) use ($locale) {
                $name = $category->getTranslation($locale, false)?->name
                    ?? $category->getTranslation(config('locales.default'), false)?->name;
                $rules = collect([
                    $category->genders->first()?->getTranslation($locale, false)?->name,
                    $category->ageEligibilityRule?->getTranslation($locale, false)?->name,
                    $category->memberGroups->map(fn ($item) => $item->getTranslation($locale, false)?->name)->filter()->join(', '),
                    $category->captureDevices->map(fn ($item) => $item->getTranslation($locale, false)?->name)->filter()->join(', '),
                    $category->processingMethods->map(fn ($item) => $item->getTranslation($locale, false)?->name)->filter()->join(', '),
                ])->filter()->join(' · ');

                return trim($name.($rules ? ': '.$rules : ''));
            })->filter()->join("\n"),
            'competition.capture_regions' => $competition->captureRegions->map(fn ($region) => trim(($region->city?->official_name ?? '').', '.($region->country?->getTranslation($locale, false)?->official_name ?? '')))->filter()->join(', '),
            default => null,
        };
    }
}
