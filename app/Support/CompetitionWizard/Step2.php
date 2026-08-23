<?php

namespace App\Support\CompetitionWizard;

/**
 * Adım 2 — Yarışma Bilgileri: Yarışma Adı, Paydaş ve İşbirlikçileri,
 * Yarışmanın Konusu, Yarışmanın Amacı. Düzenleyen kurum form verisi değildir;
 * yarışmanın Institution ilişkisi üzerinden salt okunur gösterilir. Paydaşlar
 * opsiyoneldir; konu ve amaç en fazla 1000 karakter olabilir.
 */
class Step2 implements CompetitionStep
{
    public function number(): int
    {
        return 2;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.2.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function fillable(): array
    {
        return ['name', 'partners', 'subject', 'purpose'];
    }

    public function rules(bool $isDraftSave): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'partners' => ['nullable', 'string', 'max:2000'],
            'subject' => [$required, 'string', 'max:1000'],
            'purpose' => [$required, 'string', 'max:1000'],
        ];
    }
}
