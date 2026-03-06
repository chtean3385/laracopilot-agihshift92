<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            SettingSeeder::class,
            RoomSeeder::class,
            CustomerSeeder::class,
            BookingSeeder::class,
        ]);
    }
}