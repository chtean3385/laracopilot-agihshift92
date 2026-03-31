<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use App\Services\HotelContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppTemplateSeeder extends Seeder
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

        $templates = [
            [
                'trigger_event' => 'booking.created',
                'template_name' => 'Booking Confirmation',
                'message_body'  => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}',
                'is_active'     => true,
            ],
            [
                'trigger_event' => 'checkin.tomorrow',
                'template_name' => 'Check-In Reminder',
                'message_body'  => "Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you! For any queries, please contact us.",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}',
                'is_active'     => true,
            ],
            [
                'trigger_event' => 'checkout.done',
                'template_name' => 'Check-Out & Invoice',
                'message_body'  => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again. Please share your feedback — it means the world to us!",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'     => true,
            ],
        ];

        foreach ($templates as $t) {
            WhatsAppTemplate::firstOrCreate(
                ['hotel_id' => $hotelId, 'trigger_event' => $t['trigger_event']],
                array_merge($t, ['hotel_id' => $hotelId])
            );
        }
    }
}
