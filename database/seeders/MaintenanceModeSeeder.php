<?php

namespace Database\Seeders;

use App\Models\MaintenanceMode;
use Illuminate\Database\Seeder;

/** Bakım Modu — 4 subdomain için (kapalı) başlangıç satırları. */
class MaintenanceModeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MaintenanceMode::MODULES as $module) {
            MaintenanceMode::query()->firstOrCreate(['module' => $module], ['enabled' => false]);
        }
    }
}
