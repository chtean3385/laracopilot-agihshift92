<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fix 1: activate ALL global approved templates that are currently inactive
        // (happens when sync ran before the auto-activate fix was deployed)
        DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where('approval_status', 'approved')
            ->where('is_active', false)
            ->update(['is_active' => true, 'updated_at' => now()]);

        // ── Fix 2: seed the three global templates if they don't exist yet
        $templates = [
            [
                'trigger_event'          => 'ota_booking_confirmed',
                'template_name'          => 'ota_booking_confirmed',
                'name'                   => 'OTA Booking Confirmed',
                'message_body'           => "New OTA booking received for {{hotel_name}}!\nGuest: {{guest_name}} from {{booking_source}}. Check-in: {{check_in_date}}, Check-out: {{check_out_date}}. Booking #{{booking_number}} has been created in the CRM.",
                'approval_status'        => 'approved',
                'meta_status'            => 'approved',
                'is_active'              => true,
                'has_document_attachment'=> false,
            ],
            [
                'trigger_event'          => 'ota_booking_conflict',
                'template_name'          => 'ota_booking_conflict',
                'name'                   => 'OTA Booking Conflict',
                'message_body'           => "⚠️ OTA Booking Conflict for {{hotel_name}}! Guest {{guest_name}} from {{booking_source}} (Check-in: {{check_in_date}}, Check-out: {{check_out_date}}) — {{room_type}}. Please log in to assign a room.",
                'approval_status'        => 'approved',
                'meta_status'            => 'approved',
                'is_active'              => true,
                'has_document_attachment'=> false,
            ],
            [
                'trigger_event'          => 'booking.alert.owner',
                'template_name'          => 'booking_alert_owner_v2',
                'name'                   => 'New Booking — Owner Alert',
                'message_body'           => "New booking received at {{hotel_name}}!\n\nGuest: {{guest_name}}\nRoom: {{room_number}}\nCheck-in: {{check_in_date}} | Check-out: {{check_out_date}}\nAmount: {{total_amount}} | Ref: #{{booking_number}}\n\nPlease check your CRM for full details.",
                'approval_status'        => 'approved',
                'meta_status'            => 'approved',
                'is_active'              => true,
                'has_document_attachment'=> false,
            ],
        ];

        foreach ($templates as $tmpl) {
            $existing = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $tmpl['trigger_event'])
                ->first();

            if (!$existing) {
                DB::table('whatsapp_templates')->insert(array_merge($tmpl, [
                    'hotel_id'   => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } else {
                // Ensure template_name and is_active are correct even for existing rows
                DB::table('whatsapp_templates')
                    ->where('id', $existing->id)
                    ->update([
                        'template_name'  => $tmpl['template_name'],
                        'approval_status'=> 'approved',
                        'meta_status'    => 'approved',
                        'is_active'      => true,
                        'updated_at'     => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->whereIn('trigger_event', ['ota_booking_confirmed', 'ota_booking_conflict', 'booking.alert.owner'])
            ->whereIn('template_name', ['ota_booking_confirmed', 'ota_booking_conflict', 'booking_alert_owner_v2'])
            ->delete();
    }
};
