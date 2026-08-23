<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;

/**
 * Adım 2 — Yarışma Bilgileri: Yarışma Adı, Paydaş ve İşbirlikçileri,
 * Yarışmanın Konusu, Yarışmanın Amacı. Düzenleyen kurum form verisi değildir;
 * yarışmanın Institution ilişkisi üzerinden salt okunur gösterilir. Paydaşlar
 * opsiyoneldir. Ad, konu ve amaç competition_translations tablosunda dil
 * bazında saklanır; uluslararası yarışmalarda Türkçe ve İngilizce zorunlu,
 * ulusal yarışmalarda yalnızca Türkçe zorunludur.
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

    public function isApplicable(Competition $competition): bool
    {
        return true;
    }

    public function data(Competition $competition): array
    {
        $data = ['partners' => $competition->partners];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $translation = $competition->getTranslation($locale, false);
            $data[$locale] = [
                'name' => $translation?->name,
                'subject' => $translation?->subject,
                'purpose' => $translation?->purpose,
            ];
        }

        return $data;
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (array_key_exists('partners', $validated)) {
            $competition->update(['partners' => $validated['partners']]);
        }

        $translations = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            if (! array_key_exists($locale, $validated)) {
                continue;
            }

            $translations[$locale] = array_intersect_key(
                $validated[$locale],
                array_flip(['name', 'subject', 'purpose'])
            );
        }

        if ($translations !== []) {
            $competition->upsertTranslations($translations);
        }
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $rules = [
            'partners' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $isRequired = ! $isDraftSave
                && ($locale === config('locales.default')
                    || ($locale === 'en' && $competition->requiresEnglishContent()));
            $presence = $isRequired ? 'required' : 'nullable';

            $rules["{$locale}.name"] = [$presence, 'string', 'max:255'];
            $rules["{$locale}.subject"] = [$presence, 'string', 'max:1000'];
            $rules["{$locale}.purpose"] = [$presence, 'string', 'max:1000'];
        }

        return $rules;
    }
}
