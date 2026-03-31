<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformPlanSeeder::class,
            ModuleSeeder::class,
            RolesAndPermissionsSeeder::class,
            SettingSeeder::class,
            WhatsAppTemplateSeeder::class,
            MultiHotelTestSeeder::class,
        ]);
    }
}
