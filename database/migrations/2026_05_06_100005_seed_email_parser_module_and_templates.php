<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Per-hotel module row ─────────────────────────────────────────────
        if (Schema::hasTable('hotels') && Schema::hasTable('modules')) {
            $hotelIds = DB::table('hotels')->pluck('id');
            foreach ($hotelIds as $hotelId) {
                $exists = DB::table('modules')
                    ->where('hotel_id', $hotelId)
                    ->where('slug', 'email-parser')
                    ->exists();

                if (!$exists) {
                    DB::table('modules')->insert([
                        'hotel_id'    => $hotelId,
                        'slug'        => 'email-parser',
                        'name'        => 'OTA Email Parser',
                        'description' => 'Auto-read OTA booking confirmation emails (Booking.com, Airbnb, MakeMyTrip, Goibibo, Agoda, Expedia) via IMAP every 5 minutes — auto-creates guests and bookings, detects conflicts.',
                        'is_enabled'  => false,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        }

        // ── 2. Global platform WhatsApp templates (hotel_id = null) ─────────────
        if (Schema::hasTable('whatsapp_templates')) {
            $templates = [
                [
                    'trigger_event'  => 'ota_booking_confirmed',
                    'template_name'  => 'ota_booking_confirmed',
                    'message_body'   => "New OTA booking received for {{1}}! Guest: {{2}} from {{3}}. Check-in: {{4}}, Check-out: {{5}}. Booking #{{6}} has been created in the CRM.",
                    'variables_hint' => '{{1}} hotel_name, {{2}} guest_name, {{3}} source, {{4}} check_in, {{5}} check_out, {{6}} booking_number',
                ],
                [
                    'trigger_event'  => 'ota_booking_conflict',
                    'template_name'  => 'ota_booking_conflict',
                    'message_body'   => "⚠️ OTA Booking Conflict for {{1}}! Guest {{2}} from {{3}} (Check-in: {{4}}, Check-out: {{5}}) — {{6}}. Please log in to assign a room.",
                    'variables_hint' => '{{1}} hotel_name, {{2}} guest_name, {{3}} source, {{4}} check_in, {{5}} check_out, {{6}} conflict_reason',
                ],
            ];

            $hasApproval = Schema::hasColumn('whatsapp_templates', 'approval_status');

            foreach ($templates as $t) {
                $exists = DB::table('whatsapp_templates')
                    ->whereNull('hotel_id')
                    ->where('template_name', $t['template_name'])
                    ->exists();

                if (!$exists) {
                    $row = [
                        'hotel_id'       => null,
                        'trigger_event'  => $t['trigger_event'],
                        'template_name'  => $t['template_name'],
                        'message_body'   => $t['message_body'],
                        'variables_hint' => $t['variables_hint'],
                        'is_active'      => true,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                    if ($hasApproval) {
                        $row['approval_status'] = 'pending';
                    }
                    DB::table('whatsapp_templates')->insert($row);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'email-parser')->delete();
        DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->whereIn('template_name', ['ota_booking_confirmed', 'ota_booking_conflict'])
            ->delete();
    }
};
