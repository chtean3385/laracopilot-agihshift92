<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $body =
            "New booking received at {{hotel_name}}!\n\n" .
            "Guest: {{guest_name}}\n" .
            "Room: {{room_number}}\n" .
            "Check-in: {{check_in_date}} | Check-out: {{check_out_date}}\n" .
            "Amount: {{total_amount}} | Ref: #{{booking_number}}\n\n" .
            "Please check your CRM for full details.";

        $hint = 'hotel_name, guest_name, room_number, check_in_date, check_out_date, total_amount, booking_number';

        // Insert on fresh install, update if already seeded (e.g. old body with nights + trailing variable)
        DB::table('whatsapp_templates')->updateOrInsert(
            ['trigger_event' => 'booking.alert.owner', 'hotel_id' => null],
            [
                'template_name'           => 'booking_alert_owner',
                'message_body'            => $body,
                'variables_hint'          => $hint,
                'is_active'               => true,
                'has_document_attachment' => false,
                'approval_status'         => 'pending',
                'meta_status'             => 'not_submitted',
                'updated_at'              => now(),
                'created_at'              => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('whatsapp_templates')
            ->where('trigger_event', 'booking.alert.owner')
            ->whereNull('hotel_id')
            ->delete();
    }
};
