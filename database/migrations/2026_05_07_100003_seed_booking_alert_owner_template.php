<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Global platform template for owner booking alerts (hotel_id = null).
        // Template name follows Meta naming conventions (lowercase, underscores, ≤512 chars).
        // This must be submitted to Meta by the platform admin via the Templates page.
        if (!DB::table('whatsapp_templates')->where('trigger_event', 'booking.alert.owner')->whereNull('hotel_id')->exists()) {
            DB::table('whatsapp_templates')->insert([
                'hotel_id'                => null,
                'trigger_event'           => 'booking.alert.owner',
                'template_name'           => 'booking_alert_owner',
                'message_body'            =>
                    "New booking received at {{hotel_name}}!\n\n" .
                    "Guest: {{guest_name}}\n" .
                    "Room: {{room_number}}\n" .
                    "Check-in: {{check_in_date}}\n" .
                    "Check-out: {{check_out_date}}\n" .
                    "Nights: {{nights}}\n" .
                    "Amount: {{total_amount}}\n" .
                    "Booking #: {{booking_number}}",
                'variables_hint'          => 'hotel_name, guest_name, room_number, check_in_date, check_out_date, nights, total_amount, booking_number',
                'is_active'               => true,
                'has_document_attachment' => false,
                'approval_status'         => 'pending',
                'meta_status'             => 'not_submitted',
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('whatsapp_templates')
            ->where('trigger_event', 'booking.alert.owner')
            ->whereNull('hotel_id')
            ->delete();
    }
};
