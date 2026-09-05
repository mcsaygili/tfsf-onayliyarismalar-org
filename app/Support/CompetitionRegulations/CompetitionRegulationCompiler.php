<?php

namespace App\Support\CompetitionRegulations;

use App\Models\Competition;
use App\Models\CompetitionRegulationSnapshot;
use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class CompetitionRegulationCompiler
{
    public function __construct(
        private readonly CompetitionRegulationContextBuilder $contextBuilder,
        private readonly RegulationConditionMatcher $conditionMatcher,
        private readonly RegulationTemplateRenderer $templateRenderer,
    ) {}

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
        $preview = $this->preview($competition);
        if ($preview['errors'] !== []) {
            throw new RuntimeException(collect($preview['errors'])->pluck('message')->join(' '));
        }

        return $preview['content'];
    }

    /** @return array{content: array<string, array<int, array<string, mixed>>>, errors: array<int, array{locale: string, item_code: string, message: string}>} */
    public function preview(Competition $competition): array
    {
        $competition->loadMissing('regulationInputs');
        $locales = $competition->requiresEnglishContent() ? ['tr', 'en'] : ['tr'];
        $sections = RegulationSection::active()->ordered()
            ->with(['translations', 'items' => fn ($query) => $query->active()->ordered()->with('translations')])
            ->get();
        $errors = [];
        $content = [];

        foreach ($locales as $locale) {
            $context = $this->contextBuilder->build($competition, $locale);
            $content[$locale] = $this->compileLocale($competition, $sections, $locale, $context, $errors);
        }

        return ['content' => $content, 'errors' => $errors];
    }

    /**
     * @param  Collection<int, RegulationSection>  $sections
     * @param  array<string, mixed>  $context
     * @param  array<int, array{locale: string, item_code: string, message: string}>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function compileLocale(Competition $competition, Collection $sections, string $locale, array $context, array &$errors): array
    {
        return $sections->map(function (RegulationSection $section) use ($competition, $locale, $context, &$errors) {
            $items = $section->items->flatMap(function (RegulationItem $item) use ($competition, $locale, $context, &$errors) {
                $scope = $item->render_scope ?: 'once';

                return collect($this->scopeEntries($scope, $context))->map(function (?array $entry, int $index) use ($item, $competition, $locale, $context, $scope, &$errors) {
                    $itemContext = $entry === null ? $context : array_replace_recursive($context, [$scope => $entry]);
                    if (! $this->conditionMatcher->matches($item->conditions, $itemContext)) {
                        return null;
                    }

                    try {
                        $content = $this->itemContent($item, $competition, $locale, $itemContext);
                        if (blank($content)) {
                            if ($item->is_required) {
                                throw new InvalidArgumentException('Zorunlu madde içeriği üretilemedi.');
                            }

                            return null;
                        }

                        return [
                            'id' => $item->id,
                            'code' => $item->code,
                            'content_type' => $item->content_type,
                            'render_scope' => $scope,
                            'item_version' => $item->version,
                            'occurrence' => $index + 1,
                            'content' => $content,
                        ];
                    } catch (InvalidArgumentException $exception) {
                        $errors[] = [
                            'locale' => $locale,
                            'item_code' => $item->code ?: (string) $item->id,
                            'message' => $exception->getMessage(),
                        ];

                        return null;
                    }
                });
            })->filter()->values()->all();

            return [
                'id' => $section->id,
                'code' => $section->code,
                'version' => $section->version,
                'title' => $section->getTranslation($locale, false)?->name
                    ?? $section->getTranslation(config('locales.default'), false)?->name,
                'items' => $items,
            ];
        })->filter(fn (array $section) => $section['items'] !== [])->values()->all();
    }

    /** @param array<string, mixed> $context */
    private function itemContent(RegulationItem $item, Competition $competition, string $locale, array $context): ?string
    {
        if ($item->content_type === 'institution_input') {
            return $competition->regulationInputs
                ->first(fn ($input) => $input->regulation_item_id === $item->id && $input->locale === $locale)
                ?->content;
        }

        if ($item->content_type === 'source') {
            return $this->sourceContent($item->source_key, $context);
        }

        $translation = $item->getTranslation($locale, false);
        if (! $translation && $locale === config('locales.default')) {
            $translation = $item->getTranslation(config('locales.default'), false);
        }
        $content = $translation?->content;

        return $item->content_type === 'template' && filled($content)
            ? $this->templateRenderer->render($content, $context, $item->render_scope ?: 'once')
            : $content;
    }

    /** @param array<string, mixed> $context */
    private function sourceContent(?string $key, array $context): ?string
    {
        $value = data_get($context, (string) $key);
        if ($value === null && $key === 'competition.categories') {
            $value = collect($context['categories'] ?? [])->map(fn (array $category) => collect([
                $category['name'] ?? null,
                $category['photo_rules'] ?? null,
                $category['declarations'] ?? null,
                ...($category['genders'] ?? []),
                $category['age_rule'] ?? null,
                ...($category['member_groups'] ?? []),
                ...($category['capture_devices'] ?? []),
                ...($category['processing_methods'] ?? []),
            ])->filter()->join(' · '))->all();
        }
        if ($value === null && $key === 'competition.capture_regions') {
            $value = collect($context['capture_regions'] ?? [])->pluck('name')->all();
        }
        if ($value === null && $key === 'competition.schedule') {
            $value = collect([
                data_get($context, 'competition.application_starts_at'),
                data_get($context, 'competition.application_ends_at'),
                data_get($context, 'competition.competition_ends_at'),
            ])->filter()->all();
        }
        if ($value === null && $key === 'competition.organizer') {
            $value = data_get($context, 'institution.name');
        }

        return is_array($value) ? collect($value)->filter()->join("\n") : $value;
    }

    /** @param array<string, mixed> $context @return array<int, array<string, mixed>|null> */
    private function scopeEntries(string $scope, array $context): array
    {
        return match ($scope) {
            'category' => $context['categories'] ?? [],
            'award' => $context['awards'] ?? [],
            'capture_region' => $context['capture_regions'] ?? [],
            'juror' => $context['jurors'] ?? [],
            'criterion' => $context['criteria'] ?? [],
            default => [null],
        };
    }
}
