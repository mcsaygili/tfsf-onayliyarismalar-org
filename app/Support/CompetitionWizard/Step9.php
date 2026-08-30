<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;
use App\Support\CompetitionRegulations\CompetitionRegulationCompiler;

/** Adım 9 — İlk sekiz adımdan üretilen dinamik şartnamenin salt-okunur önizlemesi. */
class Step9 implements CompetitionStep
{
    public function number(): int
    {
        return 9;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.9.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function isApplicable(Competition $competition): bool
    {
        return true;
    }

    public function data(Competition $competition): array
    {
        $preview = app(CompetitionRegulationCompiler::class)->preview($competition);
        $hasContentForEveryLocale = collect($preview['content'])->every(fn (array $sections) => $sections !== []);

        return ['regulation_ready' => $preview['errors'] === [] && $hasContentForEveryLocale ? '1' : null];
    }

    public function persist(Competition $competition, array $validated): void
    {
        // Önizleme salt-okunurdur; onaya gönderimde değişmez bir kopya oluşturulur.
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        return ['regulation_ready' => ['required', 'in:1']];
    }
}
