<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run()
    {
        DB::table('settings')->delete();

        DB::table('settings')->insert([
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