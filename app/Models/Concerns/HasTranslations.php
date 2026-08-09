<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Ayrı *_translations tablosu deseniyle çok dilli içerik desteği.
 *
 * Kullanım:
 *   class Country extends Model {
 *       use HasUuids, HasTranslations;
 *       protected array $translatedAttributes = ['official_name'];
 *   }
 *
 * Konvansiyon:
 *   - Çeviri modeli: App\Models\CountryTranslation (aynı namespace, + "Translation")
 *   - Foreign key:  country_id  (ana modelin snake_case adı + "_id")
 *   - Çeviri tablosunda 'locale' kolonu zorunlu.
 *
 * Çevrilebilir alanlar ana modelde sanki kendi alanıymış gibi okunabilir:
 *   $country->official_name  // aktif dildeki adı (yoksa fallback dile düşer)
 */
trait HasTranslations
{
    /** Tüm çevirilerle ilişki. */
    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getTranslationForeignKey());
    }

    /** Belirli dildeki çeviri modeli (yoksa fallback dile düşer). */
    public function getTranslation(?string $locale = null, bool $useFallback = true): ?Model
    {
        $locale = $locale ?: app()->getLocale();

        $translation = $this->translations->firstWhere('locale', $locale);

        if (! $translation && $useFallback) {
            $fallback = config('app.fallback_locale');
            if ($fallback !== $locale) {
                $translation = $this->translations->firstWhere('locale', $fallback);
            }
        }

        return $translation;
    }

    public function hasTranslation(?string $locale = null): bool
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translations->contains('locale', $locale);
    }

    /**
     * Diller bazında çevirileri ekle/güncelle.
     *
     * @param  array<string, array<string, mixed>>  $data  ['tr' => [...], 'en' => [...]]
     */
    public function upsertTranslations(array $data): void
    {
        foreach ($data as $locale => $attributes) {
            $attributes = array_filter(
                $attributes,
                fn ($v, $k) => in_array($k, $this->translatedAttributes ?? [], true),
                ARRAY_FILTER_USE_BOTH
            );

            if ($attributes === []) {
                continue;
            }

            $this->translations()->updateOrCreate(
                ['locale' => $locale],
                $attributes
            );
        }

        $this->unsetRelation('translations');
    }

    public function getTranslationModelName(): string
    {
        return static::class.'Translation';
    }

    public function getTranslationForeignKey(): string
    {
        return Str::snake(class_basename($this)).'_id';
    }

    /** Çevrilebilir alanları ana model üzerinden okunabilir kıl. */
    public function getAttribute($key)
    {
        if (
            in_array($key, $this->translatedAttributes ?? [], true)
            && ! array_key_exists($key, $this->attributes)
        ) {
            return $this->getTranslation()?->{$key};
        }

        return parent::getAttribute($key);
    }
}
