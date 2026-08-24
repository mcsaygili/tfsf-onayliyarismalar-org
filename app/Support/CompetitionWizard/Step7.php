<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;
use App\Models\CompetitionRegulationInput;
use App\Models\RegulationItem;

class Step7 implements CompetitionStep
{
    public function number(): int { return 7; }

    public function label(): string { return __('institution.competitions.steps.7.label'); }

    public function isImplemented(): bool { return true; }

    public function isApplicable(Competition $competition): bool { return true; }

    public function data(Competition $competition): array
    {
        $inputs = $competition->regulationInputs()->get()
            ->groupBy('regulation_item_id')
            ->map(fn ($rows) => $rows->pluck('content', 'locale')->all())
            ->all();

        return ['regulation_inputs' => $inputs];
    }

    public function persist(Competition $competition, array $validated): void
    {
        foreach ($validated['regulation_inputs'] ?? [] as $itemId => $translations) {
            foreach (['tr', 'en'] as $locale) {
                if (! array_key_exists($locale, $translations)) {
                    continue;
                }

                CompetitionRegulationInput::query()->updateOrCreate(
                    ['competition_id' => $competition->id, 'regulation_item_id' => $itemId, 'locale' => $locale],
                    ['content' => $translations[$locale] ?: null],
                );
            }
        }
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $rules = ['regulation_inputs' => ['array']];
        $required = $isDraftSave ? 'nullable' : 'required';
        $english = $competition->requiresEnglishContent() && ! $isDraftSave ? 'required' : 'nullable';

        RegulationItem::active()->where('content_type', 'institution_input')->pluck('id')->each(function (string $id) use (&$rules, $required, $english) {
            $rules["regulation_inputs.$id.tr"] = [$required, 'string', 'max:5000'];
            $rules["regulation_inputs.$id.en"] = [$english, 'string', 'max:5000'];
        });

        return $rules;
    }

    public function editableItems()
    {
        return RegulationItem::active()->where('content_type', 'institution_input')
            ->with(['translations', 'section.translations'])->ordered()->get();
    }
}
