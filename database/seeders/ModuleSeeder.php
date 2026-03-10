<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation',  'description' => 'Send automated WhatsApp messages on booking, check-in reminders, and check-out.'],
            ['slug' => 'payment_links',   'name' => 'Payment Links',        'description' => 'Generate UPI QR codes and Razorpay payment links from invoices and bookings.'],
            ['slug' => 'pathik',          'name' => 'Pathik Autofill',      'description' => 'Auto-fill Gujarat Pathik portal with guest data from the CRM via Chrome extension.'],
            ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager',  'description' => 'Sync room availability and rates with OTA platforms like eZee, STAAH, SiteMinder.'],
        ];

        foreach ($modules as $m) {
            Module::firstOrCreate(['slug' => $m['slug']], $m);
        }
    }
}
