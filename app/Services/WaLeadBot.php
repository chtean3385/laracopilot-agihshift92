<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hotel CRM Lead Qualification Bot — 8-step warm conversation flow.
 *
 * State machine via wa_contacts.bot_state (prefixed lead_step_*).
 * All collected data is upserted into whatsapp_leads in real time.
 *
 * FIXES v2:
 *  1. Button clicks (Book Demo / Call Me Back) mid-flow no longer corrupt step
 *     answers — bot re-asks the pending question after acknowledging the button.
 *  2. "Hi / Hello / Hey" mid-flow no longer wipes collected data — only restarts
 *     when the contact has NO active state (empty or terminal states).
 *  3. Form-lead prefix is now intercepted BEFORE every other check so it can
 *     never bleed into a step answer regardless of current state.
 *  4. DEMO bare keyword now only starts/restarts when NOT already in-flow,
 *     preventing accidental mid-flow resets.
 *  5. All marketing-template button strings are exhaustively normalised and
 *     checked as the very first thing so they can never fall through to steps.
 */
class WaLeadBot
{
    private const DEMO_VIDEO = 'https://www.youtube.com/watch?v=oULMSxUb9fA';

    // ── Bot messages ─────────────────────────────────────────────────────────

    private const MSG_GREETING =
        "👋 Hi there! I'm the *Hotel CRM Assistant* from *Dreams Technology* 🏨\n\n" .
        "We build smart Hotel Management Software for hotels & resorts across India — covering bookings, check-in/out, payments, invoices, and a lot more!\n\n" .
        "I'd love to show you how we can simplify your operations 😊\n\n" .
        "*May I know your name, please?*";

    private const MSG_ASK_HOTEL =
        "Lovely to meet you, *{name}*! 😊\n\n" .
        "Could you tell me the *name of your hotel or resort*?";

    private const MSG_ASK_ROOMS =
        "Wonderful! *{hotel}* — sounds like a great property! 🏨\n\n" .
        "*How many rooms* does your property have?\n_(Just type the number, e.g. 25)_";

    private const MSG_ASK_SOFTWARE =
        "Great, *{rooms} rooms* — that's a solid property 👍\n\n" .
        "Are you currently using any *hotel management software*?\n\nIf yes, please share its name. Or just say *None* if you're managing manually.";

    private const MSG_ASK_ROLE =
        "Thanks for sharing! 🙏\n\n" .
        "What is your *role at the hotel*?\n\n" .
        "1️⃣ Owner\n" .
        "2️⃣ Manager\n" .
        "3️⃣ Staff / Receptionist";

    private const MSG_ASK_CITY =
        "Got it! And which *city is your hotel* located in?";

    private const MSG_ASK_TIMELINE =
        "Lovely city! 🌆\n\n" .
        "How soon are you looking to implement a new hotel management system?\n\n" .
        "1️⃣ Immediately (ASAP)\n" .
        "2️⃣ Within 1–3 months\n" .
        "3️⃣ Just exploring for now";

    private const MSG_ASK_DEMO =
        "Excellent! 🎯 We'd love to schedule a *live demo of our Hotel CRM* for you — it only takes 20–30 minutes and you'll see exactly how it works for *{hotel}*!\n\n" .
        "What *date and time* works best for you?\n_(e.g. Tomorrow 3 PM, Monday 11 AM, etc.)_";

    private const MSG_COMPLETION =
        "🎉 Thank you, *{name}*! You're all set!\n\n" .
        "Here's a quick summary of what you shared:\n" .
        "🏨 *Hotel:* {hotel}\n" .
        "🛏 *Rooms:* {rooms}\n" .
        "👤 *Role:* {role}\n" .
        "📍 *City:* {city}\n" .
        "⏰ *Timeline:* {timeline}\n" .
        "📅 *Demo slot:* {demo}\n\n" .
        "Our team will *confirm your demo* shortly! We're excited to show you what Hotel CRM can do 🚀\n\n" .
        "_Curious about the software? Watch a quick preview here:_\n" .
        "📹 https://www.youtube.com/watch?v=oULMSxUb9fA";

    private const MSG_VIDEO =
        "Sure 😊 Here's our Hotel CRM demo video:\n" .
        "📹 https://www.youtube.com/watch?v=oULMSxUb9fA\n\n" .
        "_You can continue booking your live demo anytime 👍_";

    private const MSG_OPT_OUT =
        "No problem at all! 🙏 We respect your preference.\n\n" .
        "You've been *unsubscribed* from our messages. We won't bother you again.\n\n" .
        "_Reply *START* anytime if you'd like to hear from us again 😊_";

    private const MSG_NURTURE =
        "Absolutely, no rush! 😊 Take your time exploring.\n\n" .
        "We'll be here whenever you're ready to take the next step. Feel free to message us anytime!\n\n" .
        "_Meanwhile, check out what we offer:_\n" .
        "📹 https://www.youtube.com/watch?v=oULMSxUb9fA";

    // Exact prefix sent by website lead-capture forms
    private const FORM_LEAD_PREFIX = 'Hello! I filled in your form and would like to know more about your business.';

    // Thank-you reply sent when the form-lead prefix is detected
    private const MSG_FORM_THANK_YOU =
        "🙏 Thank you for reaching out!\n\n" .
        "We've received your enquiry and our team will get back to you shortly.\n\n" .
        "_In the meantime, feel free to say *Hi* to learn more about Dreams Hotel CRM! 😊_";

    private const MSG_HOT_ADMIN =
        "🔥 *HOT LEAD ALERT!*\n\n" .
        "👤 *Name:* {name}\n" .
        "🏨 *Hotel:* {hotel}\n" .
        "🛏 *Rooms:* {rooms}\n" .
        "👤 *Role:* {role}\n" .
        "📍 *City:* {city}\n" .
        "⏰ *When:* {timeline}\n" .
        "📅 *Demo:* {demo}\n" .
        "📞 *Phone:* {phone}\n\n" .
        "_Act fast — they want to implement IMMEDIATELY!_ 🚀";

    // ── Marketing-template button labels (normalised uppercase) ───────────────
    // Add any new button titles here so they are intercepted globally.
    private const BUTTON_BOOK_DEMO = [
        'BOOK DEMO', 'BOOK A DEMO', 'SCHEDULE DEMO', 'SCHEDULE A DEMO',
    ];
    private const BUTTON_CALL_BACK = [
        'CALL ME BACK', 'CALLBACK', 'CALL BACK', 'CALL ME',
    ];

    // ── Step → pending-question map (used to re-ask after button intercept) ──
    private const STEP_REPROMPT = [
        'lead_step_1' => self::MSG_GREETING,          // waiting for name
        // steps 2–8 are dynamic (contain {placeholders}), handled in self::repromptStep()
    ];

    // ── Entry point ──────────────────────────────────────────────────────────

    /**
     * Handle an inbound message from the given phone number.
     * Called from WhatsAppWebhookController after OTA check.
     */
    public static function handle(string $phone, ?string $text, object $platform): void
    {
        if (!$platform->saas_token || !$platform->saas_phone_number_id) return;

        $text = trim($text ?? '');

        try {
            // Fetch contact row — bail if unknown
            $contact = DB::table('wa_contacts')->where('phone', $phone)->first();
            if (!$contact) return;

            $upper        = strtoupper(preg_replace('/\s+/', ' ', $text));
            $currentState = $contact->bot_state ?? '';
            $inFlow       = str_starts_with($currentState, 'lead_step_');

            // ════════════════════════════════════════════════════════════════
            // LAYER 0 — Form-lead prefix (HIGHEST priority, checked first)
            // Must be before EVERYTHING else so it can never corrupt a step.
            // ════════════════════════════════════════════════════════════════
            if (stripos($text, self::FORM_LEAD_PREFIX) === 0) {
                self::handleFormLead($phone, $contact, $platform);
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 1 — Hard opt-out / nurture keywords (global, any state)
            // ════════════════════════════════════════════════════════════════
            if (in_array($upper, ['STOP', 'UNSUBSCRIBE', 'NO'])) {
                self::optOut($phone, $platform);
                return;
            }
            if (in_array($upper, ['MAYBE LATER', 'LATER', 'NOT NOW', 'NOT INTERESTED'])) {
                self::nurture($phone, $platform);
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 2 — Marketing-template button intercepts
            // These must be caught BEFORE the state machine. When the user is
            // mid-flow we acknowledge AND re-ask the pending question so the
            // flow is never abandoned.
            // ════════════════════════════════════════════════════════════════
            if (in_array($upper, self::BUTTON_BOOK_DEMO)) {
                self::send($platform, $phone,
                    "🎉 Great choice!\n\n" .
                    "Our team will get in touch to schedule your personalised demo shortly.\n\n" .
                    "📞 You may receive a call from our team at *+91 97252 25519*.\n\n" .
                    "We look forward to showing you Dreams Hotel CRM! 🏨"
                );
                // If mid-flow: re-ask the current pending question so the
                // qualification can continue uninterrupted.
                if ($inFlow) {
                    self::repromptStep($phone, $currentState, $platform);
                }
                return;
            }

            if (in_array($upper, self::BUTTON_CALL_BACK)) {
                self::send($platform, $phone,
                    "📞 Noted! Our team will call you back shortly.\n\n" .
                    "You may receive a call from *+91 97252 25519*.\n\n" .
                    "We look forward to connecting with you! 😊"
                );
                if ($inFlow) {
                    self::repromptStep($phone, $currentState, $platform);
                }
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 3 — Video / demo-video keyword (works in any state)
            // ════════════════════════════════════════════════════════════════
            if (in_array($upper, ['VIDEO', 'YOUTUBE', 'DEMO VIDEO'])) {
                self::send($platform, $phone, self::MSG_VIDEO);
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 4 — Terminal / non-flow states
            // ════════════════════════════════════════════════════════════════
            if (in_array($currentState, ['lead_completed', 'opted_out', 'nurture'])) {
                // Silently ignore — user has finished or unsubscribed.
                // (They can still trigger a full restart via Hi/Hello below
                //  only when state is empty, which won't be the case here.)
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 5 — Entry triggers: Hi / Hello / Hey
            //
            // KEY CHANGE: "Hi/Hello/Hey" now only restarts the flow when the
            // contact is NOT already mid-flow. This prevents accidental wipes.
            // "DEMO" bare keyword also only starts when not in-flow.
            // ════════════════════════════════════════════════════════════════
            $isGreeting = in_array($upper, ['HI', 'HELLO', 'HEY', 'START']);
            $isDemoStart = ($upper === 'DEMO');

            if ($isGreeting || $isDemoStart) {
                if (!$inFlow) {
                    // Fresh start (or coming back after terminal state reset)
                    self::send($platform, $phone, self::MSG_GREETING);
                    self::setState($phone, 'lead_step_1');
                    self::upsertLead($phone, ['current_step' => 'step_1', 'last_message_at' => now()]);
                    return;
                }
                // Already mid-flow: treat "hi" as noise — re-ask pending question
                // so the user knows where they are, rather than silently ignoring.
                self::repromptStep($phone, $currentState, $platform);
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 6 — Unrecognised message outside any flow
            // ════════════════════════════════════════════════════════════════
            if (!$inFlow) {
                // Not in a flow and not a known trigger — invite them to start.
                self::send($platform, $phone,
                    "👋 Hello! Type *Hi* to learn how Dreams Hotel CRM can help your property 🏨"
                );
                return;
            }

            // ════════════════════════════════════════════════════════════════
            // LAYER 7 — Active flow state machine
            // ════════════════════════════════════════════════════════════════
            match ($currentState) {
                'lead_step_1' => self::handleStep1($phone, $text, $platform),
                'lead_step_2' => self::handleStep2($phone, $text, $platform),
                'lead_step_3' => self::handleStep3($phone, $text, $platform),
                'lead_step_4' => self::handleStep4($phone, $text, $platform),
                'lead_step_5' => self::handleStep5($phone, $text, $platform),
                'lead_step_6' => self::handleStep6($phone, $text, $platform),
                'lead_step_7' => self::handleStep7($phone, $text, $platform),
                'lead_step_8' => self::handleStep8($phone, $text, $platform),
                default       => null,
            };

        } catch (\Throwable $e) {
            Log::error("WaLeadBot error for {$phone}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Form-lead handler ────────────────────────────────────────────────────

    /**
     * Called when the inbound message starts with the website form-lead prefix.
     *
     * Behaviour:
     *  - If NOT yet in flow AND follow_up not already sent → send the approved
     *    follow_up_after_lead template and mark state as 'follow_up_sent'.
     *  - If already in flow → send a simple thank-you text (never corrupt step).
     *  - If follow_up already sent → send a simple thank-you text (idempotent).
     */
    private static function handleFormLead(string $phone, object $contact, object $platform): void
    {
        $currentState = $contact->bot_state ?? '';
        $inFlow       = str_starts_with($currentState, 'lead_step_');

        if (!$inFlow && $currentState !== 'follow_up_sent') {
            // First time — send the approved template
            self::sendFollowUpAfterLead($phone, $contact, $platform);
        } else {
            // Mid-flow or already sent — just send a polite thank-you text,
            // never touch the bot_state or lead data.
            self::send($platform, $phone, self::MSG_FORM_THANK_YOU);
        }
    }

    // ── Re-prompt helper ─────────────────────────────────────────────────────

    /**
     * Re-send the question that corresponds to the current bot state.
     * Called after a button intercept so the user knows where they are.
     */
    private static function repromptStep(string $phone, string $state, object $platform): void
    {
        // Fetch lead data for personalisation
        $lead  = DB::table('whatsapp_leads')->where('phone', $phone)->first();
        $name  = $lead?->name ?? 'there';
        $hotel = $lead?->hotel_name ?? 'your property';
        $rooms = $lead?->room_count ?? '—';

        $msg = match ($state) {
            'lead_step_1' => self::MSG_GREETING,
            'lead_step_2' => str_replace('{name}', $name, self::MSG_ASK_HOTEL),
            'lead_step_3' => str_replace('{hotel}', $hotel, self::MSG_ASK_ROOMS),
            'lead_step_4' => str_replace('{rooms}', $rooms, self::MSG_ASK_SOFTWARE),
            'lead_step_5' => self::MSG_ASK_ROLE,
            'lead_step_6' => self::MSG_ASK_CITY,
            'lead_step_7' => self::MSG_ASK_TIMELINE,
            'lead_step_8' => str_replace('{hotel}', $hotel, self::MSG_ASK_DEMO),
            default       => null,
        };

        if ($msg) {
            // Small preamble so the re-prompt doesn't feel abrupt
            self::send($platform, $phone,
                "_(To continue your demo booking, please answer the question below 👇)_\n\n" . $msg
            );
        }
    }

    // ── Step handlers ────────────────────────────────────────────────────────

    /** Step 1: Received name */
    private static function handleStep1(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $name = ucwords(strtolower(trim($text)));
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'display_name' => $name,
            'updated_at'   => now(),
        ]);
        self::upsertLead($phone, ['name' => $name, 'current_step' => 'step_2', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_2');
        self::send($platform, $phone, str_replace('{name}', $name, self::MSG_ASK_HOTEL));
    }

    /** Step 2: Received hotel name */
    private static function handleStep2(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $hotel = trim($text);
        self::upsertLead($phone, ['hotel_name' => $hotel, 'current_step' => 'step_3', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_3');
        self::send($platform, $phone, str_replace('{hotel}', $hotel, self::MSG_ASK_ROOMS));
    }

    /** Step 3: Received room count */
    private static function handleStep3(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $rooms = trim($text);
        self::upsertLead($phone, ['room_count' => $rooms, 'current_step' => 'step_4', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_4');
        self::send($platform, $phone, str_replace('{rooms}', $rooms, self::MSG_ASK_SOFTWARE));
    }

    /** Step 4: Received current software */
    private static function handleStep4(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $software = trim($text);
        self::upsertLead($phone, ['current_system' => $software, 'current_step' => 'step_5', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_5');
        self::send($platform, $phone, self::MSG_ASK_ROLE);
    }

    /** Step 5: Received role (1=Owner, 2=Manager, 3=Staff) */
    private static function handleStep5(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $roleMap = [
            '1'      => 'Owner',
            '2'      => 'Manager',
            '3'      => 'Staff / Receptionist',
            'owner'  => 'Owner',
            'manager'=> 'Manager',
            'staff'  => 'Staff / Receptionist',
        ];
        $lower = strtolower(trim($text));
        $role  = $roleMap[$text] ?? $roleMap[$lower] ?? null;

        if (!$role) {
            foreach (['owner', 'manager', 'staff', 'receptionist'] as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $role = ucfirst($keyword);
                    break;
                }
            }
        }
        if (!$role) $role = ucwords(strtolower(trim($text)));

        self::upsertLead($phone, ['role' => $role, 'current_step' => 'step_6', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_6');
        self::send($platform, $phone, self::MSG_ASK_CITY);
    }

    /** Step 6: Received city */
    private static function handleStep6(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $city = trim($text);
        self::upsertLead($phone, ['city' => $city, 'current_step' => 'step_7', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_7');
        self::send($platform, $phone, self::MSG_ASK_TIMELINE);
    }

    /** Step 7: Received urgency/timeline */
    private static function handleStep7(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $timelineMap = [
            '1' => 'Immediately (ASAP)',
            '2' => 'Within 1–3 months',
            '3' => 'Just exploring for now',
        ];
        $lower    = strtolower(trim($text));
        $timeline = $timelineMap[$text] ?? null;

        if (!$timeline) {
            if (str_contains($lower, 'immediate') || str_contains($lower, 'asap') || str_contains($lower, 'urgent')) {
                $timeline = 'Immediately (ASAP)';
            } elseif (str_contains($lower, 'month') || str_contains($lower, '1-3') || str_contains($lower, '1–3')) {
                $timeline = 'Within 1–3 months';
            } else {
                $timeline = ucwords(strtolower(trim($text)));
            }
        }

        $lead  = DB::table('whatsapp_leads')->where('phone', $phone)->first();
        $hotel = $lead?->hotel_name ?? 'your property';

        self::upsertLead($phone, ['implementation_timeline' => $timeline, 'current_step' => 'step_8', 'last_message_at' => now()]);
        self::setState($phone, 'lead_step_8');
        self::send($platform, $phone, str_replace('{hotel}', $hotel, self::MSG_ASK_DEMO));
    }

    /** Step 8: Received demo date/time → complete the flow */
    private static function handleStep8(string $phone, string $text, object $platform): void
    {
        if (empty($text)) return;

        $demo = trim($text);
        $lead = DB::table('whatsapp_leads')->where('phone', $phone)->first();

        // ── Lead scoring ─────────────────────────────────────────────────────
        $role     = strtolower($lead?->role ?? '');
        $rooms    = (int) preg_replace('/[^0-9]/', '', $lead?->room_count ?? '0');
        $timeline = strtolower($lead?->implementation_timeline ?? '');

        $isOwner     = str_contains($role, 'owner');
        $isManager   = str_contains($role, 'manager');
        $isImmediate = str_contains($timeline, 'immediate') || str_contains($timeline, 'asap');
        $isBig       = $rooms >= 20;
        $isMedium    = $rooms >= 5 && $rooms < 20;

        if ($isOwner && $isBig && $isImmediate) {
            $score = 'hot';
        } elseif ($isManager || $isMedium) {
            $score = 'warm';
        } else {
            $score = 'cold';
        }

        self::upsertLead($phone, [
            'demo_datetime'   => $demo,
            'lead_status'     => $score,
            'lead_score'      => $score,
            'current_step'    => 'completed',
            'last_message_at' => now(),
        ]);

        DB::table('wa_contacts')->where('phone', $phone)->update([
            'lead_status' => $score,
            'bot_state'   => 'lead_completed',
            'updated_at'  => now(),
        ]);

        $msg = self::MSG_COMPLETION;
        $msg = str_replace('{name}',     $lead?->name ?? 'there',                $msg);
        $msg = str_replace('{hotel}',    $lead?->hotel_name ?? 'your hotel',     $msg);
        $msg = str_replace('{rooms}',    $lead?->room_count ?? '—',              $msg);
        $msg = str_replace('{role}',     $lead?->role ?? '—',                    $msg);
        $msg = str_replace('{city}',     $lead?->city ?? '—',                    $msg);
        $msg = str_replace('{timeline}', $lead?->implementation_timeline ?? '—', $msg);
        $msg = str_replace('{demo}',     $demo,                                  $msg);
        self::send($platform, $phone, $msg);

        if ($score === 'hot') {
            self::notifyAdmin($platform, $phone, $lead, $demo);
        }
    }

    // ── Form-lead follow-up template sender ─────────────────────────────────

    /**
     * Send the `follow_up_after_lead` approved Meta template.
     * Only fires once per contact (idempotency guard on bot_state).
     */
    private static function sendFollowUpAfterLead(string $phone, object $contact, object $platform): void
    {
        $name   = $contact->display_name ?? null;
        $params = $name ? [$name] : [];

        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

        $parameters = array_map(fn($val) => ['type' => 'text', 'text' => (string) $val], $params);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $numericPhone,
            'type'              => 'template',
            'template'          => [
                'name'       => 'follow_up_after_lead',
                'language'   => ['code' => 'en_US'],
                'components' => $parameters
                    ? [['type' => 'body', 'parameters' => $parameters]]
                    : [],
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", $payload);

            if ($response->successful()) {
                Log::info("WaLeadBot: follow_up_after_lead template sent to {$phone}");
            } else {
                Log::warning("WaLeadBot: follow_up_after_lead template failed for {$phone}", ['error' => $response->json()]);
            }
        } catch (\Throwable $e) {
            Log::error("WaLeadBot: follow_up_after_lead exception for {$phone}: " . $e->getMessage());
        }

        DB::table('wa_contacts')->where('phone', $phone)->update([
            'bot_state'  => 'follow_up_sent',
            'updated_at' => now(),
        ]);

        self::upsertLead($phone, ['current_step' => 'follow_up', 'last_message_at' => now()]);
    }

    // ── Opt-out / Nurture ────────────────────────────────────────────────────

    private static function optOut(string $phone, object $platform): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'subscribed'      => false,
            'unsubscribed_at' => now(),
            'bot_state'       => 'opted_out',
            'updated_at'      => now(),
        ]);
        self::upsertLead($phone, ['opt_out' => true, 'lead_status' => 'opted_out', 'last_message_at' => now()]);
        self::send($platform, $phone, self::MSG_OPT_OUT);
        Log::info("WaLeadBot: {$phone} opted out.");
    }

    private static function nurture(string $phone, object $platform): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'lead_status' => 'nurture',
            'bot_state'   => 'nurture',
            'updated_at'  => now(),
        ]);
        self::upsertLead($phone, ['lead_status' => 'nurture', 'last_message_at' => now()]);
        self::send($platform, $phone, self::MSG_NURTURE);
        Log::info("WaLeadBot: {$phone} moved to nurture.");
    }

    // ── Admin notification (HOT lead) ────────────────────────────────────────

    public static function notifyAdmin(object $platform, string $phone, ?object $lead, string $demo): void
    {
        $adminPhone = DB::table('platform_whatsapp_settings')->value('admin_notify_phone');
        if (!$adminPhone) {
            Log::info("WaLeadBot: HOT lead from {$phone} — no admin_notify_phone configured, skipping alert.");
            return;
        }

        $msg = self::MSG_HOT_ADMIN;
        $msg = str_replace('{name}',     $lead?->name ?? '—',                    $msg);
        $msg = str_replace('{hotel}',    $lead?->hotel_name ?? '—',              $msg);
        $msg = str_replace('{rooms}',    $lead?->room_count ?? '—',              $msg);
        $msg = str_replace('{role}',     $lead?->role ?? '—',                    $msg);
        $msg = str_replace('{city}',     $lead?->city ?? '—',                    $msg);
        $msg = str_replace('{timeline}', $lead?->implementation_timeline ?? '—', $msg);
        $msg = str_replace('{demo}',     $demo,                                  $msg);
        $msg = str_replace('{phone}',    $phone,                                 $msg);

        $numericPhone = preg_replace('/[^0-9]/', '', $adminPhone);
        if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

        try {
            $response = Http::timeout(10)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $numericPhone,
                    'type'              => 'text',
                    'text'              => ['body' => $msg, 'preview_url' => false],
                ]);

            if (!$response->successful()) {
                Log::warning('WaLeadBot: admin HOT alert failed', ['error' => $response->json()]);
            }
        } catch (\Throwable $e) {
            Log::error('WaLeadBot: admin notify exception: ' . $e->getMessage());
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function setState(string $phone, string $state): void
    {
        DB::table('wa_contacts')->where('phone', $phone)->update([
            'bot_state'  => $state,
            'updated_at' => now(),
        ]);
    }

    private static function upsertLead(string $phone, array $fields): void
    {
        $existing = DB::table('whatsapp_leads')->where('phone', $phone)->first();
        if ($existing) {
            DB::table('whatsapp_leads')->where('phone', $phone)->update(
                array_merge($fields, ['updated_at' => now()])
            );
        } else {
            DB::table('whatsapp_leads')->insert(
                array_merge(['phone' => $phone, 'created_at' => now(), 'updated_at' => now()], $fields)
            );
        }
    }

    private static function send(object $platform, string $phone, string $text): void
    {
        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) === 10) $numericPhone = '91' . $numericPhone;

        try {
            $response = Http::timeout(10)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v22.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $numericPhone,
                    'type'              => 'text',
                    'text'              => ['body' => $text, 'preview_url' => false],
                ]);

            if (!$response->successful()) {
                Log::warning("WaLeadBot send failed for {$phone}", ['error' => $response->json()]);
            }
        } catch (\Throwable $e) {
            Log::error("WaLeadBot send exception for {$phone}: " . $e->getMessage());
        }
    }
}