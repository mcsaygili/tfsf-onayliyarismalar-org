<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

/** Referans veriler — standart Türkiye öğrenim durumu listesi. */
class EducationLevelSeeder extends Seeder
{
    private const LEVELS = [
        ['sort_order' => 10, 'tr' => 'İlkokul', 'en' => 'Primary School'],
        ['sort_order' => 20, 'tr' => 'Ortaokul', 'en' => 'Secondary School'],
        ['sort_order' => 30, 'tr' => 'Lise', 'en' => 'High School'],
        ['sort_order' => 40, 'tr' => 'Ön Lisans', 'en' => 'Associate Degree'],
        ['sort_order' => 50, 'tr' => 'Lisans', 'en' => 'Bachelor\'s Degree'],
        ['sort_order' => 60, 'tr' => 'Yüksek Lisans', 'en' => 'Master\'s Degree'],
        ['sort_order' => 70, 'tr' => 'Doktora', 'en' => 'Doctorate'],
    ];

    public function run(): void
    {
        foreach (self::LEVELS as $level) {
            $educationLevel = EducationLevel::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $level['tr']))
                ->first();

            if (! $educationLevel) {
                $educationLevel = EducationLevel::create([
                    'sort_order' => $level['sort_order'],
                    'status' => true,
                ]);
            } else {
                $educationLevel->update(['sort_order' => $level['sort_order']]);
            }

            $educationLevel->upsertTranslations([
                'tr' => ['name' => $level['tr']],
                'en' => ['name' => $level['en']],
            ]);
        }
    }
}
