<?php

namespace Database\Seeders;

use App\Models\PhotoCategory;
use Illuminate\Database\Seeder;

/** Referans veriler — Üye portfolyosu için standart fotoğraf kategorisi listesi. */
class PhotoCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['sort_order' => 10, 'tr' => 'Portre', 'en' => 'Portrait'],
        ['sort_order' => 20, 'tr' => 'Doğa', 'en' => 'Nature'],
        ['sort_order' => 30, 'tr' => 'Sokak', 'en' => 'Street'],
        ['sort_order' => 40, 'tr' => 'Manzara', 'en' => 'Landscape'],
        ['sort_order' => 50, 'tr' => 'Belgesel', 'en' => 'Documentary'],
        ['sort_order' => 60, 'tr' => 'Spor', 'en' => 'Sports'],
        ['sort_order' => 70, 'tr' => 'Mimari', 'en' => 'Architecture'],
        ['sort_order' => 80, 'tr' => 'Soyut', 'en' => 'Abstract'],
        ['sort_order' => 90, 'tr' => 'Siyah-Beyaz', 'en' => 'Black & White'],
        ['sort_order' => 100, 'tr' => 'Diğer', 'en' => 'Other'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            $photoCategory = PhotoCategory::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $category['tr']))
                ->first();

            if (! $photoCategory) {
                $photoCategory = PhotoCategory::create([
                    'sort_order' => $category['sort_order'],
                    'status' => true,
                ]);
            } else {
                $photoCategory->update(['sort_order' => $category['sort_order']]);
            }

            $photoCategory->upsertTranslations([
                'tr' => ['name' => $category['tr']],
                'en' => ['name' => $category['en']],
            ]);
        }
    }
}
