<?php

namespace Database\Seeders;

use App\Services\HotelContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Global platform templates (hotel_id = null) ─────────────────
        // These are the templates approved on Meta for the shared platform number.
        // Used by all Basic-plan hotels. After deploying to a new environment,
        // enter the API credentials in Platform Admin → WhatsApp Settings, then
        // click "Sync from Meta" to update the approval statuses automatically.
        //
        // Rules enforced: no variable at the very start or end of the body.
        $globalTemplates = [
            [
                'trigger_event'    => 'booking.created',
                'template_name'    => 'booking_confrim_crm',
                'message_body'     => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.",
                'approval_status'  => 'approved',
                'meta_template_id' => '946426251471676',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'checkin.tomorrow',
                'template_name'    => 'check_in_reminder_day_before',
                'message_body'     => "Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you!",
                'approval_status'  => 'approved',
                'meta_template_id' => '941731028473106',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'checkin.done',
                'template_name'    => 'rrival_elcome',
                'message_body'     => "Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou're all checked in!\n\ud83d\udeaa Room: {{room_number}} ({{room_type}})\n\ud83d\udcc5 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay. Please don't hesitate to ask if you need anything.",
                'approval_status'  => 'approved',
                'meta_template_id' => '979908241129195',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'check_out_and_bill',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe would love to host you again!",
                'approval_status'         => 'approved',
                'meta_template_id'        => '2851015818584226',
                'meta_status'             => 'approved',
                'is_active'               => true,
                'has_document_attachment' => false,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'check_out_bill_with_pdf',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a wonderful stay.\n\nPlease find your invoice attached.\nInvoice: {{invoice_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe would love to host you again!",
                'approval_status'         => 'pending',
                'meta_template_id'        => null,
                'meta_status'             => 'not_submitted',
                'is_active'               => false,
                'has_document_attachment' => true,
            ],
            [
                'trigger_event'    => 'feedback.request',
                'template_name'    => 'eedback_equest',
                'message_body'     => "Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe'd love to hear your feedback to help us serve you better. Please share your experience whenever you get a moment.\n\nWe look forward to welcoming you again! \ud83c\udf1f",
                'approval_status'  => 'approved',
                'meta_template_id' => '1466324025233517',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'payment.received',
                'template_name'    => 'payment_receipt',
                'message_body'     => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\nBooking Ref: {{booking_number}}, Balance Due: {{balance_due}}.\n\nWe look forward to welcoming you again at {{hotel_name}}!",
                'approval_status'  => 'pending',
                'meta_template_id' => null,
                'meta_status'      => 'not_submitted',
                'is_active'        => false,
            ],
        ];

        foreach ($globalTemplates as $tpl) {
            // Match by template_name (unique) rather than trigger_event alone,
            // because checkout.done legitimately has two rows (text + PDF).
            $existing = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('template_name', $tpl['template_name'])
                ->first();

            if ($existing) {
                // Update to ensure correct values; never downgrade an approved one to pending
                $update = [
                    'trigger_event'           => $tpl['trigger_event'],
                    'message_body'            => $tpl['message_body'],
                    'has_document_attachment' => $tpl['has_document_attachment'] ?? false,
                    'updated_at'              => now(),
                ];
                if (!empty($tpl['meta_template_id']) && !$existing->meta_template_id) {
                    $update['meta_template_id'] = $tpl['meta_template_id'];
                    $update['meta_status']       = $tpl['meta_status'];
                    $update['approval_status']   = $tpl['approval_status'];
                    $update['is_active']         = $tpl['is_active'];
                } elseif ($existing->approval_status !== 'approved' && $tpl['approval_status'] === 'approved') {
                    $update['approval_status']   = 'approved';
                    $update['meta_template_id']  = $tpl['meta_template_id'];
                    $update['meta_status']       = $tpl['meta_status'];
                    $update['is_active']         = $tpl['is_active'];
                }
                DB::table('whatsapp_templates')->where('id', $existing->id)->update($update);
            } else {
                DB::table('whatsapp_templates')->insert(
                    array_merge($tpl, [
                        'hotel_id'                => null,
                        'has_document_attachment' => $tpl['has_document_attachment'] ?? false,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ])
                );
            }
        }

        // Clean up stale duplicates per event, but preserve any row with has_document_attachment=true
        // alongside the canonical text template for that event.
        $events = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->select('trigger_event')
            ->distinct()
            ->pluck('trigger_event');

        foreach ($events as $event) {
            $rows = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $event)
                ->orderByDesc('approval_status')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            // Keep all rows that have has_document_attachment=true (PDF templates)
            // and the best approved text template; delete remaining duplicates.
            $keepIds   = $rows->where('has_document_attachment', true)->pluck('id')->toArray();
            $textRows  = $rows->where('has_document_attachment', false);
            $canonical = $textRows->first(); // already sorted approved-first
            if ($canonical) {
                $keepIds[] = $canonical->id;
            }

            DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $event)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        // ── 2. Hotel-specific templates for every hotel ────────────────────
        // Each hotel gets its own editable copy. On the shared number, the
        // *global* templates above are what actually gets sent. Hotel-specific
        // templates are used for Pro+ hotels with their own Meta number.
        $hotelTemplates = [
            [
                'trigger_event'  => 'booking.created',
                'template_name'  => 'Booking Confirmation',
                'message_body'   => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'checkin.tomorrow',
                'template_name'  => 'Check-In Reminder',
                'message_body'   => "Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you!",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'checkin.done',
                'template_name'  => 'Arrival Welcome',
                'message_body'   => "Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou're all checked in!\n\ud83d\udeaa Room: {{room_number}} ({{room_type}})\n\ud83d\udcc5 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay. Please don't hesitate to ask if you need anything.",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{room_type}}, {{check_out_date}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'Check-Out & Invoice',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint'          => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'               => true,
                'has_document_attachment' => false,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'Check-Out & Invoice (PDF)',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a wonderful stay.\n\nPlease find your invoice attached.\nInvoice: {{invoice_number}}\nTotal Amount: \u20b9{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint'          => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'               => false,
                'has_document_attachment' => true,
            ],
            [
                'trigger_event'  => 'payment.received',
                'template_name'  => 'Payment Receipt',
                'message_body'   => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\nBooking Ref: {{booking_number}}, Balance Due: {{balance_due}}.\n\nWe look forward to welcoming you again at {{hotel_name}}!",
                'variables_hint' => '{{guest_name}}, {{amount_paid}}, {{payment_method}}, {{booking_number}}, {{balance_due}}, {{hotel_name}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'feedback.request',
                'template_name'  => 'Feedback Request',
                'message_body'   => "Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! \ud83d\ude4f\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe'd love to hear your feedback to help us serve you better. Please share your experience whenever you get a moment.\n\nWe look forward to welcoming you again! \ud83c\udf1f",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{check_in_date}}, {{check_out_date}}',
                'is_active'      => true,
            ],
        ];

        $hotelIds = DB::table('hotels')->pluck('id');
        foreach ($hotelIds as $hotelId) {
            app(HotelContext::class)->setHotel($hotelId);
            foreach ($hotelTemplates as $t) {
                $isPdf = !empty($t['has_document_attachment']);

                // Match on (hotel_id, trigger_event, has_document_attachment) so text and PDF
                // variants can coexist for the same event (e.g. checkout.done).
                $existing = DB::table('whatsapp_templates')
                    ->where('hotel_id', $hotelId)
                    ->where('trigger_event', $t['trigger_event'])
                    ->where('has_document_attachment', $isPdf)
                    ->first();

                $data = array_merge($t, [
                    'hotel_id'                => $hotelId,
                    'has_document_attachment' => $isPdf,
                    'approval_status'         => 'pending',
                    'meta_template_id'        => null,
                    'meta_status'             => 'not_submitted',
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                if ($existing) {
                    DB::table('whatsapp_templates')->where('id', $existing->id)->update(array_merge($data, ['created_at' => $existing->created_at]));
                } else {
                    DB::table('whatsapp_templates')->insert($data);
                }
            }
        }
    }
}
