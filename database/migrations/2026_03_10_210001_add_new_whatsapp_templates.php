<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $existing = DB::table('whatsapp_templates')->pluck('trigger_event')->toArray();
        $templates = [
            [
                'trigger_event'  => 'checkin.done',
                'template_name'  => 'Arrival Welcome',
                'message_body'   => "Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou're all checked in!\n📍 Room: {{room_number}} ({{room_type}})\n📅 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay. Please don't hesitate to ask if you need anything.",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{room_type}}, {{check_out_date}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'payment.received',
                'template_name'  => 'Payment Receipt',
                'message_body'   => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\n\n📋 Booking: {{booking_number}}\n💰 Balance Due: {{balance_due}}\n\nThank you! — {{hotel_name}}",
                'variables_hint' => '{{guest_name}}, {{amount_paid}}, {{payment_method}}, {{booking_number}}, {{balance_due}}, {{hotel_name}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'feedback.request',
                'template_name'  => 'Feedback Request',
                'message_body'   => "Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! 🙏\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe'd love to hear your feedback to help us serve you better. Please share your experience whenever you get a moment.\n\nWe look forward to welcoming you again! 🌟",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{check_in_date}}, {{check_out_date}}',
                'is_active'      => true,
            ],
        ];
        foreach ($templates as $t) {
            if (!in_array($t['trigger_event'], $existing)) {
                DB::table('whatsapp_templates')->insert(array_merge($t, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
    public function down(): void
    {
        DB::table('whatsapp_templates')->whereIn('trigger_event', ['checkin.done','payment.received','feedback.request'])->delete();
    }
};
