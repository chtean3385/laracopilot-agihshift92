<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Services\HotelContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
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

        app(HotelContext::class)->setHotel($hotelId);

        $modules = [
            ['slug' => 'whatsapp',          'name' => 'WhatsApp Automation',       'description' => 'Send automated WhatsApp messages on booking, check-in reminders, and check-out.'],
            ['slug' => 'payment_links',     'name' => 'Payment Links',             'description' => 'Generate UPI QR codes and Razorpay payment links from invoices and bookings.'],
            ['slug' => 'pathik',            'name' => 'Pathik Autofill',           'description' => 'Auto-fill Gujarat Pathik portal with guest data from the CRM via Chrome extension.'],
            ['slug' => 'channel_manager',   'name' => 'OTA Channel Manager',       'description' => 'Sync room availability and rates with OTA platforms like eZee, STAAH, SiteMinder.'],
            ['slug' => 'time-slot-pricing', 'name' => 'Time Slot & Hourly Pricing','description' => 'Enable time-slot and hourly room pricing modes.'],
            ['slug' => 'extra-billing',     'name' => 'Extra Billing',             'description' => 'Add post-booking charges (food, laundry, services, etc.) to occupied or confirmed bookings and reflect them on the final bill.'],
        ];

        foreach ($modules as $m) {
            Module::firstOrCreate(['hotel_id' => $hotelId, 'slug' => $m['slug']], array_merge($m, ['hotel_id' => $hotelId]));
        }
    }
}
