<?php

namespace Database\Seeders;

use App\Models\InstitutionType;
use Illuminate\Database\Seeder;

/** Referans veriler — standart kurum türü listesi. */
class InstitutionTypeSeeder extends Seeder
{
    private const TYPES = [
        ['sort_order' => 10, 'tr' => 'Dernek', 'en' => 'Association'],
        ['sort_order' => 20, 'tr' => 'Belediye', 'en' => 'Municipality'],
        ['sort_order' => 30, 'tr' => 'Üniversite', 'en' => 'University'],
        ['sort_order' => 40, 'tr' => 'Vakıf', 'en' => 'Foundation'],
        ['sort_order' => 50, 'tr' => 'Kamu Kurumu', 'en' => 'Public Institution'],
        ['sort_order' => 60, 'tr' => 'Özel Kuruluş', 'en' => 'Private Organization'],
        ['sort_order' => 70, 'tr' => 'Diğer', 'en' => 'Other'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            $institutionType = InstitutionType::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $type['tr']))
                ->first();

            if (! $institutionType) {
                $institutionType = InstitutionType::create([
                    'sort_order' => $type['sort_order'],
                    'status' => true,
                ]);
            } else {
                $institutionType->update(['sort_order' => $type['sort_order']]);
            }

            $institutionType->upsertTranslations([
                'tr' => ['name' => $type['tr']],
                'en' => ['name' => $type['en']],
            ]);
        }
    }
}
