<?php

namespace Database\Seeders;

use App\Models\PhotoTechnique;
use Illuminate\Database\Seeder;

/**
 * Referans veriler — yarışmacının fotoğrafın çekim/işlem yöntemi hakkında
 * beyan verebileceği standart teknik listesi. İlk 6 madde kullanıcı isteğinde
 * belirtildi; kalan 4 madde (Çoklu Pozlama, Uzun Pozlama, Işık Ressamlığı,
 * Kızılötesi Fotoğrafçılık) uluslararası yarışma beyan listelerinde (PSA/FIAP
 * tarzı) sık görülen, ayrıca beyan edilmesi gereken çekim teknikleridir.
 */
class PhotoTechniqueSeeder extends Seeder
{
    private const TECHNIQUES = [
        ['sort_order' => 10, 'tr' => 'Yapay Zekâ Kullanıldı', 'en' => 'Artificial Intelligence Used'],
        ['sort_order' => 20, 'tr' => 'HDR Kullanıldı', 'en' => 'HDR Used'],
        ['sort_order' => 30, 'tr' => 'Focus Stacking Kullanıldı', 'en' => 'Focus Stacking Used'],
        ['sort_order' => 40, 'tr' => 'Panorama Birleştirmesi Yapıldı', 'en' => 'Panorama Stitching'],
        ['sort_order' => 50, 'tr' => 'Kompozit Çalışma Yapıldı', 'en' => 'Composite Work'],
        ['sort_order' => 60, 'tr' => 'Yoğun Dijital Manipülasyon Yapıldı', 'en' => 'Heavy Digital Manipulation'],
        ['sort_order' => 70, 'tr' => 'Çoklu Pozlama (Kamera İçi)', 'en' => 'In-camera Multiple Exposure'],
        ['sort_order' => 80, 'tr' => 'Uzun Pozlama', 'en' => 'Long Exposure'],
        ['sort_order' => 90, 'tr' => 'Işık Ressamlığı', 'en' => 'Light Painting'],
        ['sort_order' => 100, 'tr' => 'Kızılötesi Fotoğrafçılık', 'en' => 'Infrared Photography'],
    ];

    public function run(): void
    {
        foreach (self::TECHNIQUES as $technique) {
            $photoTechnique = PhotoTechnique::query()
                ->whereHas('translations', fn ($q) => $q->where('locale', 'tr')->where('name', $technique['tr']))
                ->first();

            if (! $photoTechnique) {
                $photoTechnique = PhotoTechnique::create([
                    'sort_order' => $technique['sort_order'],
                    'status' => true,
                ]);
            } else {
                $photoTechnique->update(['sort_order' => $technique['sort_order']]);
            }

            $photoTechnique->upsertTranslations([
                'tr' => ['name' => $technique['tr']],
                'en' => ['name' => $technique['en']],
            ]);
        }
    }
}
