<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Module;
use App\Models\Setting;
use App\Models\BookingWidgetSetting;
use App\Models\BookingPaymentReference;
use App\Models\PlatformWhatsAppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicBookingController extends Controller
{
    // ── Hotel/module resolution helpers ──────────────────────────────────────

    private function getActiveHotel(string $slug): ?Hotel
    {
        return Hotel::where('slug', $slug)->where('status', 'active')->first();
    }

    private function widgetEnabled(int $hotelId): bool
    {
        return Module::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('slug', 'booking-widget')
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Hotel-scoped HMAC token — injected into every public form/JS.
     * Validated on all POST endpoints instead of standard CSRF.
     */
    private function hotelToken(string $slug): string
    {
        return hash_hmac('sha256', $slug, config('app.key'));
    }

    private function verifyHotelToken(Request $request, string $slug): bool
    {
        $token = $request->input('_widget_token') ?? $request->header('X-Widget-Token');
        if (!$token) return false;
        return hash_equals($this->hotelToken($slug), $token);
    }

    // ── Public views ─────────────────────────────────────────────────────────

    public function show(string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) abort(404);

        $widgetSettings = BookingWidgetSetting::where('hotel_id', $hotel->id)->first()
            ?? new BookingWidgetSetting(['hotel_id' => $hotel->id]);
        $hotelSettings  = Setting::where('hotel_id', $hotel->id)->first();
        $widgetToken    = $this->hotelToken($slug);

        return view('public.booking.show', compact('hotel', 'widgetSettings', 'hotelSettings', 'widgetToken'));
    }

    public function iframe(string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) abort(404);

        $widgetSettings = BookingWidgetSetting::where('hotel_id', $hotel->id)->first()
            ?? new BookingWidgetSetting(['hotel_id' => $hotel->id]);
        $hotelSettings  = Setting::where('hotel_id', $hotel->id)->first();
        $widgetToken    = $this->hotelToken($slug);

        return view('public.booking.iframe', compact('hotel', 'widgetSettings', 'hotelSettings', 'widgetToken'));
    }

    public function embedJs(string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) {
            return response('// Booking widget not available', 404)
                ->header('Content-Type', 'application/javascript');
        }

        $ws          = BookingWidgetSetting::where('hotel_id', $hotel->id)->first();
        $color       = $ws->primary_color ?? '#6366f1';
        $btnText     = htmlspecialchars($ws->button_text ?? 'Book Now', ENT_QUOTES);
        $iframeUrl   = url("/book/{$slug}/iframe");
        $widgetToken = $this->hotelToken($slug);

        $js = <<<JS
(function() {
    var color    = "{$color}";
    var btnText  = "{$btnText}";
    var iframeUrl = "{$iframeUrl}";

    var btn = document.createElement('button');
    btn.innerText = btnText;
    btn.style.cssText = 'position:fixed;bottom:28px;right:28px;z-index:99998;background:' + color + ';color:#fff;border:none;border-radius:50px;padding:14px 26px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,.2);font-family:system-ui,sans-serif;letter-spacing:.01em;transition:opacity .2s,transform .15s;';
    btn.onmouseover = function() { this.style.opacity='0.9'; this.style.transform='scale(1.04)'; };
    btn.onmouseout  = function() { this.style.opacity='1';   this.style.transform='scale(1)'; };

    var overlay = document.createElement('div');
    overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);backdrop-filter:blur(2px);';

    var panel = document.createElement('div');
    panel.style.cssText = 'position:absolute;right:0;top:0;height:100%;width:100%;max-width:520px;background:#fff;box-shadow:-4px 0 40px rgba(0,0,0,.18);';

    var iframe = document.createElement('iframe');
    iframe.src = iframeUrl;
    iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;';
    iframe.allow = 'autoplay';

    panel.appendChild(iframe);
    overlay.appendChild(panel);

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) { overlay.style.display = 'none'; }
    });
    btn.addEventListener('click', function() { overlay.style.display = 'block'; });

    document.body.appendChild(btn);
    document.body.appendChild(overlay);
})();
JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=300');
    }

    // ── Availability (POST, hotel-token validated) ────────────────────────────

    public function availability(Request $request, string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (!$this->verifyHotelToken($request, $slug)) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        if (!$checkIn || !$checkOut || $checkIn >= $checkOut) {
            return response()->json(['error' => 'Invalid dates'], 422);
        }

        // Enforce booking rules from widget settings
        $ws = BookingWidgetSetting::where('hotel_id', $hotel->id)->first();
        $minAdvanceHours = (int) ($ws->min_advance_hours ?? 2);
        $maxAdvanceDays  = (int) ($ws->max_advance_days  ?? 365);

        $checkInDt = Carbon::parse($checkIn)->startOfDay();
        if ($minAdvanceHours > 0 && $checkInDt->lt(now()->addHours($minAdvanceHours))) {
            return response()->json(['error' => "Minimum {$minAdvanceHours} hours advance booking required."], 422);
        }
        if ($checkInDt->gt(now()->addDays($maxAdvanceDays))) {
            return response()->json(['error' => "Cannot book more than {$maxAdvanceDays} days in advance."], 422);
        }

        $nights = (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400);
        if ($nights < 1) {
            return response()->json(['error' => 'Minimum 1 night'], 422);
        }

        $rooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('status', '!=', 'maintenance')
            ->get();

        // Only confirmed/checked_in bookings block availability (pending bookings don't hold rooms)
        $conflictingIds = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereNotNull('room_id')
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->pluck('room_id')
            ->toArray();

        $types = [];
        foreach ($rooms as $room) {
            $t = $room->type ?: 'Standard';
            if (!isset($types[$t])) {
                $types[$t] = [
                    'available' => 0,
                    'total'     => 0,
                    'room'      => $room,
                ];
            }
            $types[$t]['total']++;
            if (!in_array($room->id, $conflictingIds)) {
                $types[$t]['available']++;
            }
        }

        $result = [];
        foreach ($types as $typeName => $typeData) {
            // Always return all types (available + sold-out) so guest can request even if dates are full
            $r = $typeData['room'];
            // Use best available price regardless of pricing_type
            $price = (float) $r->price_per_night;
            if ($price <= 0 && !empty($r->hourly_rate)) {
                $price = (float) $r->hourly_rate;
            }
            $result[] = [
                'type'            => $typeName,
                'price_per_night' => $price,
                'total_price'     => $price * $nights,
                'nights'          => $nights,
                'capacity'        => (int) $r->capacity,
                'description'     => $r->description ?? '',
                'amenities'       => $r->amenities ?? '',
                'photo_url'       => $r->photo_url ?? null,
                'available_count' => $typeData['available'],
                'available'       => $typeData['available'] > 0,
            ];
        }

        usort($result, fn($a, $b) => $b['available'] <=> $a['available'] ?: $a['price_per_night'] <=> $b['price_per_night']);

        return response()->json([
            'types'            => $result,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'nights'           => $nights,
            'widget_token'     => $this->hotelToken($slug),
            'show_prices'      => (bool) ($ws->show_prices ?? true),
            'show_room_photos' => (bool) ($ws->show_room_photos ?? false),
        ]);
    }

    // ── Booking store (POST /book/{slug}/book) ───────────────────────────────

    public function store(Request $request, string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) abort(404);

        if (!$this->verifyHotelToken($request, $slug)) abort(403, 'Invalid widget token.');

        $request->validate([
            'check_in'         => 'required|date|after_or_equal:today',
            'check_out'        => 'required|date|after:check_in',
            'room_type'        => 'required|string|max:100',
            'guest_name'       => 'required|string|max:255',
            'phone'            => 'required|string|max:30',
            'adults'           => 'required|integer|min:1',
            'children'         => 'nullable|integer|min:0',
            'special_requests' => 'nullable|string|max:500',
        ]);

        // Enforce booking rules from widget settings
        $ws             = BookingWidgetSetting::where('hotel_id', $hotel->id)->first();
        $minAdvHours    = (int) ($ws->min_advance_hours ?? 2);
        $maxAdvDays     = (int) ($ws->max_advance_days  ?? 365);
        $autoConfirm    = $ws && $ws->auto_confirm;
        $checkIn        = $request->check_in;
        $checkOut       = $request->check_out;
        $roomType       = $request->room_type;
        $phone          = $request->phone;
        $email          = $request->email;

        $checkInDt = Carbon::parse($checkIn)->startOfDay();
        if ($minAdvHours > 0 && $checkInDt->lt(now()->addHours($minAdvHours))) {
            return back()->withErrors(['check_in' => "Minimum {$minAdvHours} hours advance booking required."]);
        }
        if ($checkInDt->gt(now()->addDays($maxAdvDays))) {
            return back()->withErrors(['check_in' => "Cannot book more than {$maxAdvDays} days in advance."]);
        }

        // Find or create Customer
        $customer = Customer::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where(function ($q) use ($phone, $email) {
                $q->where('phone', $phone);
                if ($email) $q->orWhere('email', $email);
            })
            ->first();

        if (!$customer) {
            $customer = Customer::withoutGlobalScopes()->create([
                'hotel_id'  => $hotel->id,
                'name'      => $request->guest_name,
                'phone'     => $phone,
                'email'     => $email ?: null,
                'id_number' => '',
            ]);
        }

        // Room assignment decision tree
        $matchedRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('status', '!=', 'maintenance')
            ->where('type', $roomType)
            ->get();

        // Only confirmed/checked_in bookings block room assignment (pending bookings don't hold rooms)
        $conflictingIds = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereNotNull('room_id')
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->pluck('room_id')
            ->toArray();

        $assignedRoom = null;
        $hasConflict  = false;
        $conflictType = null;

        if ($matchedRooms->isEmpty()) {
            $hasConflict  = true;
            $conflictType = 'no_room_matched';
        } else {
            $freeRooms = $matchedRooms->reject(fn($r) => in_array($r->id, $conflictingIds));
            if ($freeRooms->isEmpty()) {
                $hasConflict  = true;
                $conflictType = 'dates_overlap';
            } else {
                $assignedRoom = $freeRooms->first();
            }
        }

        $nights        = (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400);
        $sampleRoom    = $assignedRoom ?? $matchedRooms->first();
        $pricePerNight = $sampleRoom ? (float) $sampleRoom->price_per_night : 0;
        $total         = $pricePerNight * $nights;

        $status = $hasConflict
            ? 'pending_room_assignment'
            : ($autoConfirm ? 'confirmed' : 'website_pending');

        $bookingNumber = 'BK-WEB-' . strtoupper(Str::random(6));

        $booking = Booking::withoutGlobalScopes()->create([
            'hotel_id'         => $hotel->id,
            'booking_number'   => $bookingNumber,
            'customer_id'      => $customer->id,
            'room_id'          => $assignedRoom?->id,
            'check_in_date'    => $checkIn,
            'check_out_date'   => $checkOut,
            'nights'           => $nights,
            'adults'           => $request->adults,
            'children'         => $request->children ?? 0,
            'total_amount'     => $total,
            'advance_payment'  => 0,
            'balance_due'      => $total,
            'status'           => $status,
            'payment_status'   => 'pending',
            'special_requests' => $request->special_requests,
            'source'           => 'website',
            'ota_conflict'     => $hasConflict,
        ]);

        if ($hasConflict && Schema::hasTable('ota_booking_conflicts')) {
            DB::table('ota_booking_conflicts')->insert([
                'hotel_id'            => $hotel->id,
                'booking_id'          => $booking->id,
                'conflict_type'       => $conflictType === 'no_room_matched' ? 'no_room_matched' : 'dates_overlap',
                'requested_room_type' => $roomType,
                'check_in_date'       => $checkIn,
                'check_out_date'      => $checkOut,
                'resolved'            => false,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        $this->notifyAdmin($hotel, $customer, $booking, $roomType, $hasConflict);
        $this->notifyGuest($hotel, $customer, $booking, $roomType, $hasConflict);

        return redirect()->route('public.booking.confirm', [
            'slug' => $slug,
            'ref'  => $bookingNumber,
        ]);
    }

    // ── Confirmation page ─────────────────────────────────────────────────────

    public function confirm(string $slug, string $ref)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel || !$this->widgetEnabled($hotel->id)) abort(404);

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('booking_number', $ref)
            ->with(['customer', 'room'])
            ->firstOrFail();

        $ws            = BookingWidgetSetting::where('hotel_id', $hotel->id)->first();
        $hotelSettings = Setting::where('hotel_id', $hotel->id)->first();
        $widgetToken   = $this->hotelToken($slug);

        return view('public.booking.confirm', compact('hotel', 'booking', 'ws', 'hotelSettings', 'widgetToken'));
    }

    // ── ICS calendar download ─────────────────────────────────────────────────

    public function ical(string $slug, string $ref)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel) abort(404);

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('booking_number', $ref)
            ->with('customer')
            ->firstOrFail();

        $hs       = Setting::where('hotel_id', $hotel->id)->first();
        $hotelName = $hs->resort_name ?? $hotel->name;
        $guestName = $booking->customer->name ?? 'Guest';
        $uid       = $ref . '@' . parse_url(config('app.url'), PHP_URL_HOST);

        $dtStart  = Carbon::parse($booking->check_in_date)->format('Ymd') . 'T120000';
        $dtEnd    = Carbon::parse($booking->check_out_date)->format('Ymd') . 'T110000';
        $dtstamp  = now()->format('Ymd\THis\Z');

        $ics = "BEGIN:VCALENDAR\r\n"
             . "VERSION:2.0\r\n"
             . "PRODID:-//Resort CRM//EN\r\n"
             . "BEGIN:VEVENT\r\n"
             . "UID:{$uid}\r\n"
             . "DTSTAMP:{$dtstamp}\r\n"
             . "DTSTART:{$dtStart}\r\n"
             . "DTEND:{$dtEnd}\r\n"
             . "SUMMARY:Hotel Stay at {$hotelName}\r\n"
             . "DESCRIPTION:Booking {$ref} for {$guestName}\r\n"
             . "STATUS:CONFIRMED\r\n"
             . "END:VEVENT\r\n"
             . "END:VCALENDAR\r\n";

        return response($ics, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=booking-{$ref}.ics");
    }

    // ── Guest submits UPI payment reference ───────────────────────────────────

    public function submitPaymentRef(Request $request, string $slug)
    {
        $hotel = $this->getActiveHotel($slug);
        if (!$hotel) abort(404);

        if (!$this->verifyHotelToken($request, $slug)) abort(403, 'Invalid widget token.');

        $request->validate([
            'booking_ref' => 'required|string',
            'utr'         => 'required|string|max:50',
        ]);

        $booking = Booking::withoutGlobalScopes()
            ->where('hotel_id', $hotel->id)
            ->where('booking_number', $request->booking_ref)
            ->firstOrFail();

        BookingPaymentReference::create([
            'booking_id'       => $booking->id,
            'payment_type'     => 'upi',
            'reference_number' => $request->utr,
            'amount'           => $request->amount ?? 0,
            'submitted_by'     => 'guest',
            'verified'         => false,
        ]);

        return back()->with('payment_submitted', true);
    }

    // ── Guest WA confirmation notification ────────────────────────────────────

    private function notifyGuest(Hotel $hotel, Customer $customer, Booking $booking, string $roomType, bool $isConflict): void
    {
        try {
            if (!$customer->phone) return;

            $platform = PlatformWhatsAppSetting::instance();
            if (!$platform || !$platform->is_saas_active || !$platform->saas_token || !$platform->saas_phone_number_id) {
                return;
            }

            $guestPhone = preg_replace('/[^0-9]/', '', $customer->phone);
            if (!$guestPhone) return;
            if (!str_starts_with($guestPhone, '91') && strlen($guestPhone) === 10) {
                $guestPhone = '91' . $guestPhone;
            }

            $hotelSettings = Setting::where('hotel_id', $hotel->id)->first();
            $hotelName     = $hotelSettings->resort_name ?? $hotel->name;

            $preferredName = $isConflict ? 'website_booking_guest_conflict' : 'website_booking_guest_confirm';

            // Try platform-level specific template first, then fall back to hotel's booking_confirmation
            $tmpl = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('template_name', $preferredName)
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->first();

            $useSpecific = (bool) $tmpl;

            if (!$tmpl) {
                // Fall back to hotel's active booking.created template
                $tmpl = DB::table('whatsapp_templates')
                    ->where('hotel_id', $hotel->id)
                    ->where('trigger_event', 'booking.created')
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$tmpl) {
                Log::info("Website booking guest WA skipped: no suitable template found.", [
                    'hotel_id' => $hotel->id, 'booking_number' => $booking->booking_number,
                ]);
                return;
            }

            // Specific templates carry custom parameter sets; fallback (booking.created) uses standard vars
            $components = $useSpecific ? (
                $isConflict ? [
                    ['type' => 'body', 'parameters' => [
                        ['type' => 'text', 'text' => $customer->name ?? 'Guest'],
                        ['type' => 'text', 'text' => $hotelName],
                        ['type' => 'text', 'text' => $booking->booking_number],
                        ['type' => 'text', 'text' => $booking->check_in_date->format('d M Y')],
                        ['type' => 'text', 'text' => $booking->check_out_date->format('d M Y')],
                    ]],
                ] : [
                    ['type' => 'body', 'parameters' => [
                        ['type' => 'text', 'text' => $customer->name ?? 'Guest'],
                        ['type' => 'text', 'text' => $hotelName],
                        ['type' => 'text', 'text' => $booking->booking_number],
                        ['type' => 'text', 'text' => $roomType],
                        ['type' => 'text', 'text' => $booking->check_in_date->format('d M Y')],
                        ['type' => 'text', 'text' => $booking->check_out_date->format('d M Y')],
                    ]],
                ]
            ) : [
                // Standard booking_confirmation parameters (name, hotel, booking#, check-in, check-out)
                ['type' => 'body', 'parameters' => [
                    ['type' => 'text', 'text' => $customer->name ?? 'Guest'],
                    ['type' => 'text', 'text' => $hotelName],
                    ['type' => 'text', 'text' => $booking->booking_number],
                    ['type' => 'text', 'text' => $booking->check_in_date->format('d M Y')],
                    ['type' => 'text', 'text' => $booking->check_out_date->format('d M Y')],
                ]],
            ];

            \Illuminate\Support\Facades\Http::timeout(10)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $guestPhone,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $tmpl->meta_template_name ?? $tmpl->template_name ?? $preferredName,
                        'language'   => ['code' => 'en_US'],
                        'components' => $components,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Website booking guest WA notification failed: ' . $e->getMessage(), [
                'hotel_id' => $hotel->id,
            ]);
        }
    }

    // ── Admin WA notification ─────────────────────────────────────────────────

    private function notifyAdmin(Hotel $hotel, Customer $customer, Booking $booking, string $roomType, bool $isConflict): void
    {
        try {
            $platform = PlatformWhatsAppSetting::instance();
            if (!$platform || !$platform->is_saas_active || !$platform->saas_token || !$platform->saas_phone_number_id) {
                return;
            }

            // Resolve admin phone from hotel_users (is_hotel_admin=1) → fallback to hotels.phone
            $adminUser = DB::table('hotel_users')
                ->join('users', 'users.id', '=', 'hotel_users.user_id')
                ->where('hotel_users.hotel_id', $hotel->id)
                ->where('hotel_users.is_hotel_admin', 1)
                ->whereNotNull('users.phone')
                ->where('users.phone', '!=', '')
                ->select('users.phone')
                ->first();

            $hotelPhone = $adminUser
                ? preg_replace('/[^0-9]/', '', $adminUser->phone)
                : preg_replace('/[^0-9]/', '', $hotel->phone ?? '');

            if (!$hotelPhone) return;
            if (!str_starts_with($hotelPhone, '91') && strlen($hotelPhone) === 10) {
                $hotelPhone = '91' . $hotelPhone;
            }

            $hotelSettings = Setting::where('hotel_id', $hotel->id)->first();
            $hotelName     = $hotelSettings->resort_name ?? $hotel->name;

            $templateName = $isConflict ? 'ota_booking_conflict' : 'website_booking_received';
            $tmpl = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('template_name', $templateName)
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->first();

            if (!$tmpl) {
                Log::info("Website booking WA skipped: template '{$templateName}' not approved yet.", [
                    'hotel_id' => $hotel->id, 'booking_number' => $booking->booking_number,
                ]);
                return;
            }

            $metaName   = $tmpl->meta_template_name ?? $templateName;
            $components = $isConflict ? [
                ['type' => 'body', 'parameters' => [
                    ['type' => 'text', 'text' => $hotelName],
                    ['type' => 'text', 'text' => $customer->name ?? 'Guest'],
                    ['type' => 'text', 'text' => 'Website Booking'],
                    ['type' => 'text', 'text' => $booking->check_in_date->format('d M Y')],
                    ['type' => 'text', 'text' => $booking->check_out_date->format('d M Y')],
                    ['type' => 'text', 'text' => "Room type '{$roomType}' not available for selected dates"],
                ]],
            ] : [
                ['type' => 'body', 'parameters' => [
                    ['type' => 'text', 'text' => $hotelName],
                    ['type' => 'text', 'text' => $customer->name ?? 'Guest'],
                    ['type' => 'text', 'text' => $customer->phone ?? ''],
                    ['type' => 'text', 'text' => $roomType],
                    ['type' => 'text', 'text' => $booking->check_in_date->format('d M Y')],
                    ['type' => 'text', 'text' => $booking->check_out_date->format('d M Y')],
                    ['type' => 'text', 'text' => $booking->booking_number],
                ]],
            ];

            \Illuminate\Support\Facades\Http::timeout(10)
                ->withToken($platform->saas_token)
                ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $hotelPhone,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $metaName,
                        'language'   => ['code' => 'en_US'],
                        'components' => $components,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Website booking admin WA notification failed: ' . $e->getMessage(), [
                'hotel_id' => $hotel->id,
            ]);
        }
    }
}
