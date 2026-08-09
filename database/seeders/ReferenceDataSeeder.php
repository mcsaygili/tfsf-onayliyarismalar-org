<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Legacy CodeIgniter (tfsf-ecosystem) referans verisini taşır:
 *   sys_ref_ulke(+_detay), sys_ref_sehir(+_detay)
 *       →  countries(+country_translations), cities(+city_translations)
 *
 * Veri kaynağı: database/seeders/data/*.sql (tfsf-org-tr'den birebir
 * kopyalanan legacy export'ları — bkz. proje planı, "aynı data" kararı).
 * Legacy UUID'ler birincil anahtar olarak korunur; böylece şehir→ülke
 * ilişkisi doğrudan eşleşir, seeder idempotent çalışır VE iki proje
 * arasında aynı kayıt aynı id'yi taşır.
 */
class ReferenceDataSeeder extends Seeder
{
    /** Legacy language_uuid → uygulama locale eşlemesi. */
    private const LOCALE_MAP = [
        '4f8f37e9-75a8-49d1-8ed7-b8d39918ef22' => 'en',
        '66e36c6a-9a62-49db-b7ee-1a43043267ce' => 'tr',
    ];

    public function run(): void
    {
        $dir = database_path('seeders/data');

        $this->seedCountries("{$dir}/sys_ref_ulke_upsert.sql");
        $this->seedCountryTranslations("{$dir}/sys_ref_ulke_detay_upsert.sql");
        $this->seedCities("{$dir}/sys_ref_sehir_upsert.sql");
        $this->seedCityTranslations("{$dir}/sys_ref_sehir_detay_upsert.sql");

        $this->command?->info(sprintf(
            'Referans veri: %d ülke, %d şehir.',
            DB::table('countries')->count(),
            DB::table('cities')->count(),
        ));
    }

    private function seedCountries(string $file): void
    {
        $now = now();

        foreach ($this->rows($file) as $v) {
            // (uuid, status, iso_alpa_2_kodu, iso_alpa_3_kodu, deleted, created, updated)
            [$uuid, $status, $iso2, $iso3, $deleted] = $v;

            if ((int) $deleted === 1) {
                continue;
            }

            DB::table('countries')->updateOrInsert(
                ['id' => $uuid],
                [
                    'iso_alpha2' => $this->nullable($iso2),
                    'iso_alpha3' => $this->nullable($iso3),
                    'status' => (int) $status === 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedCountryTranslations(string $file): void
    {
        $countryIds = DB::table('countries')->pluck('id')->flip();
        $now = now();

        foreach ($this->rows($file) as $v) {
            // (ulke_uuid, language_uuid, resmi_adi, kisa_adi, uyruk)
            [$countryId, $languageUuid, $official, $short, $nationality] = $v;

            $locale = self::LOCALE_MAP[$languageUuid] ?? null;
            if ($locale === null || ! isset($countryIds[$countryId])) {
                continue;
            }

            DB::table('country_translations')->updateOrInsert(
                ['country_id' => $countryId, 'locale' => $locale],
                [
                    'id' => $this->translationId('country_translations', $countryId, $locale),
                    'official_name' => $official,
                    'short_name' => $this->nullable($short),
                    'nationality' => $this->nullable($nationality),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedCities(string $file): void
    {
        $countryIds = DB::table('countries')->pluck('id')->flip();
        $now = now();

        foreach ($this->rows($file) as $v) {
            // (uuid, ref_ulke, status, deleted, created, updated)
            [$uuid, $refUlke, $status, $deleted] = $v;

            if ((int) $deleted === 1 || ! isset($countryIds[$refUlke])) {
                continue;
            }

            DB::table('cities')->updateOrInsert(
                ['id' => $uuid],
                [
                    'country_id' => $refUlke,
                    'status' => (int) $status === 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedCityTranslations(string $file): void
    {
        $cityIds = DB::table('cities')->pluck('id')->flip();
        $now = now();

        foreach ($this->rows($file) as $v) {
            // (sehir_uuid, language_uuid, resmi_adi)
            [$cityId, $languageUuid, $official] = $v;

            $locale = self::LOCALE_MAP[$languageUuid] ?? null;
            if ($locale === null || ! isset($cityIds[$cityId])) {
                continue;
            }

            DB::table('city_translations')->updateOrInsert(
                ['city_id' => $cityId, 'locale' => $locale],
                [
                    'id' => $this->translationId('city_translations', $cityId, $locale),
                    'official_name' => $official,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /** Var olan çeviri satırının id'sini korur, yoksa yeni uuid üretir. */
    private function translationId(string $table, string $fkValue, string $locale): string
    {
        $fk = $table === 'country_translations' ? 'country_id' : 'city_id';

        $existing = DB::table($table)
            ->where($fk, $fkValue)
            ->where('locale', $locale)
            ->value('id');

        return $existing ?: (string) Str::uuid();
    }

    /**
     * Bir SQL dosyasındaki INSERT satırlarını okuyup her birinin VALUES
     * demetini diziye çevirerek üretir.
     *
     * @return \Generator<int, array<int, string>>
     */
    private function rows(string $file): \Generator
    {
        if (! is_file($file)) {
            return;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $start = strpos($line, 'VALUES (');
            if ($start === false) {
                continue;
            }
            $start += strlen('VALUES (');

            $end = strpos($line, ') ON DUPLICATE', $start);
            if ($end === false) {
                $end = strrpos($line, ')');
            }
            if ($end === false) {
                continue;
            }

            yield $this->parseTuple(substr($line, $start, $end - $start));
        }

        fclose($handle);
    }

    /**
     * `'a','b',NULL` biçimindeki demeti, tek tırnaklı ve kaçışlı (\\', '')
     * değerleri gözeterek diziye ayrıştırır.
     *
     * @return array<int, string>
     */
    private function parseTuple(string $tuple): array
    {
        $values = [];
        $len = strlen($tuple);
        $i = 0;

        while ($i < $len) {
            while ($i < $len && ($tuple[$i] === ' ' || $tuple[$i] === ',')) {
                $i++;
            }
            if ($i >= $len) {
                break;
            }

            if ($tuple[$i] === "'") {
                $i++;
                $buf = '';
                while ($i < $len) {
                    $ch = $tuple[$i];
                    if ($ch === '\\' && $i + 1 < $len) {
                        $buf .= $tuple[$i + 1];
                        $i += 2;

                        continue;
                    }
                    if ($ch === "'") {
                        if ($i + 1 < $len && $tuple[$i + 1] === "'") {
                            $buf .= "'";
                            $i += 2;

                            continue;
                        }
                        $i++;
                        break;
                    }
                    $buf .= $ch;
                    $i++;
                }
                $values[] = $buf;
            } else {
                $buf = '';
                while ($i < $len && $tuple[$i] !== ',') {
                    $buf .= $tuple[$i];
                    $i++;
                }
                $values[] = trim($buf);
            }
        }

        return $values;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return ($value === '' || strcasecmp($value, 'NULL') === 0) ? null : $value;
    }
}
