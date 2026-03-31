<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve hotel — for installer it's already created; for dev seeding create default
        $hotelId = DB::table('hotels')->value('id');

        if (!$hotelId) {
            $hotelId = DB::table('hotels')->insertGetId([
                'name'       => 'Default Hotel',
                'slug'       => 'default-hotel',
                'status'     => 'active',
                'plan'       => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Only seed if this hotel doesn't have settings yet
        $exists = DB::table('settings')->where('hotel_id', $hotelId)->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'hotel_id'            => $hotelId,
                'resort_name'         => 'Azure Paradise Resort and Spa',
                'address'             => '45 Beachside Boulevard, Calangute, Goa 403516 India',
                'phone'               => '+91 832 267 8900',
                'email'               => 'reservations@azureparadise.com',
                'website'             => 'www.azureparadise.com',
                'gst_number'          => '30AABCU9603R1ZX',
                'tax_rate'            => '12',
                'currency'            => 'INR',
                'currency_symbol'     => 'Rs',
                'check_in_time'       => '14:00',
                'check_out_time'      => '11:00',
                'cancellation_policy' => 'Free cancellation up to 48 hours before check-in. 50% charge within 24 to 48 hours.',
                'logo'                => null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}
