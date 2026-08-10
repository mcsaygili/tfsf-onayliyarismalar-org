<?php

namespace Database\Seeders;

use App\Models\EquipmentBrand;
use Illuminate\Database\Seeder;

/** Referans veriler — Ekipman Kataloğu için küratörlü başlangıç marka listesi. */
class EquipmentBrandSeeder extends Seeder
{
    private const BRANDS = [
        'Canon', 'Nikon', 'Sony', 'Fujifilm', 'Panasonic', 'OM System',
        'Sigma', 'Tamron', 'Zeiss', 'Leica', 'Pentax', 'Hasselblad',
        'Manfrotto', 'DJI', 'Diğer',
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $name) {
            EquipmentBrand::query()->firstOrCreate(['name' => $name], ['status' => true]);
        }
    }
}
