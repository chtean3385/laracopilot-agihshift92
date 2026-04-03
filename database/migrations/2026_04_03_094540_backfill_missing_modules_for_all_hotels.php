<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure every hotel has all 6 expected module rows.
     * Safe to run multiple times — uses insertOrIgnore / existence check.
     */
    public function up(): void
    {
        $expectedModules = [
            [
                'slug'        => 'whatsapp',
                'name'        => 'WhatsApp Automation',
                'description' => 'Automated WhatsApp messages.',
            ],
            [
                'slug'        => 'payment_links',
                'name'        => 'Payment Links',
                'description' => 'Generate UPI QR codes and payment links.',
            ],
            [
                'slug'        => 'pathik',
                'name'        => 'Pathik Autofill',
                'description' => 'Gujarat Pathik portal autofill.',
            ],
            [
                'slug'        => 'channel_manager',
                'name'        => 'OTA Channel Manager',
                'description' => 'Sync with OTA platforms.',
            ],
            [
                'slug'        => 'time-slot-pricing',
                'name'        => 'Time Slot Pricing',
                'description' => 'Enable fixed time-block (per-slot) room pricing and booking.',
            ],
            [
                'slug'        => 'hourly-pricing',
                'name'        => 'Hourly Pricing',
                'description' => 'Enable per-hour room pricing and booking.',
            ],
        ];

        $hotelIds = DB::table('hotels')->pluck('id');

        foreach ($hotelIds as $hotelId) {
            foreach ($expectedModules as $mod) {
                $exists = DB::table('modules')
                    ->where('hotel_id', $hotelId)
                    ->where('slug', $mod['slug'])
                    ->exists();

                if (!$exists) {
                    DB::table('modules')->insert([
                        'hotel_id'    => $hotelId,
                        'slug'        => $mod['slug'],
                        'name'        => $mod['name'],
                        'description' => $mod['description'],
                        'is_enabled'  => false,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive — no rollback action needed
    }
};
