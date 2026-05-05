<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'trigger_event'    => 'ota_booking_confirmed',
                'template_name'    => 'ota_booking_confirmed',
                'name'             => 'OTA Booking Confirmed',
                'message_body'     => "New OTA booking received for {{hotel_name}}!\nGuest: {{guest_name}} from {{booking_source}}. Check-in: {{check_in_date}}, Check-out: {{check_out_date}}. Booking #{{booking_number}} has been created in the CRM.",
                'approval_status'  => 'approved',
                'meta_status'      => 'approved',
                'is_active'        => true,
                'has_document_attachment' => false,
            ],
            [
                'trigger_event'    => 'ota_booking_conflict',
                'template_name'    => 'ota_booking_conflict',
                'name'             => 'OTA Booking Conflict',
                'message_body'     => "⚠️ OTA Booking Conflict for {{hotel_name}}! Guest {{guest_name}} from {{booking_source}} (Check-in: {{check_in_date}}, Check-out: {{check_out_date}}) — {{room_type}}. Please log in to assign a room.",
                'approval_status'  => 'approved',
                'meta_status'      => 'approved',
                'is_active'        => true,
                'has_document_attachment' => false,
            ],
            [
                'trigger_event'    => 'booking.alert.owner',
                'template_name'    => 'booking_alert_owner_v2',
                'name'             => 'New Booking — Owner Alert',
                'message_body'     => "New booking received at {{hotel_name}}!\n\nGuest: {{guest_name}}\nRoom: {{room_number}}\nCheck-in: {{check_in_date}} | Check-out: {{check_out_date}}\nAmount: {{total_amount}} | Ref: #{{booking_number}}\n\nPlease check your CRM for full details.",
                'approval_status'  => 'approved',
                'meta_status'      => 'approved',
                'is_active'        => true,
                'has_document_attachment' => false,
            ],
        ];

        foreach ($templates as $tmpl) {
            $exists = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $tmpl['trigger_event'])
                ->exists();

            if (!$exists) {
                DB::table('whatsapp_templates')->insert(array_merge($tmpl, [
                    'hotel_id'   => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->whereIn('trigger_event', ['ota_booking_confirmed', 'ota_booking_conflict', 'booking.alert.owner'])
            ->delete();
    }
};
