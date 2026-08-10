<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

/** Referans veriler — Ekipman Kataloğu için standart ekipman türü listesi. */
class EquipmentTypeSeeder extends Seeder
{
    private const TYPES = [
        ['sort_order' => 10, 'tr' => 'Kamera Gövdesi', 'en' => 'Camera Body'],
        ['sort_order' => 20, 'tr' => 'Objektif', 'en' => 'Lens'],
        ['sort_order' => 30, 'tr' => 'Flaş', 'en' => 'Flash'],
        ['sort_order' => 40, 'tr' => 'Tripod', 'en' => 'Tripod'],
        ['sort_order' => 50, 'tr' => 'Filtre', 'en' => 'Filter'],
        ['sort_order' => 60, 'tr' => 'Diğer', 'en' => 'Other'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            $equipmentType = EquipmentType::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $type['tr']))
                ->first();

            if (! $equipmentType) {
                $equipmentType = EquipmentType::create([
                    'sort_order' => $type['sort_order'],
                    'status' => true,
                ]);
            } else {
                $equipmentType->update(['sort_order' => $type['sort_order']]);
            }

            $equipmentType->upsertTranslations([
                'tr' => ['name' => $type['tr']],
                'en' => ['name' => $type['en']],
            ]);
        }
    }
}
