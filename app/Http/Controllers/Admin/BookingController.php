<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        // Mark OTA email import banner as "seen" for this hotel
        $hotelId = (int) session('crm_hotel_id');
        session(["ota_email_seen_{$hotelId}" => now()->toDateTimeString()]);

        $query = Booking::with(['customer', 'room']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%$search%"))
                  ->orWhere('booking_number', 'like', "%$search%");
            });
        }
        if ($request->date_from) $query->whereDate('check_in_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('check_out_date', '<=', $request->date_to);
        $bookings = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.bookings.index', compact('bookings'));
    }

    public function availableTimeSlots(Request $request)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $validated = $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'date'    => 'required|date_format:Y-m-d',
        ]);

        $available = (new \App\Services\SlotConflictService())->availableSlotIdsForRoom(
            (int) $validated['room_id'],
            $validated['date']
        );

        return response()->json(['available_slot_ids' => $available]);
    }

    public function availableRooms(Request $request)
    {
        if (!session('crm_logged_in')) return response()->json(['error' => 'Unauthenticated'], 401);

        $hotelId = (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));

        $unavailableRoomIds = collect();

        // ── Per-night: check_in + check_out ────────────────────────────────
        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn  = $request->input('check_in');
            $checkOut = $request->input('check_out');

            $nightIds = \App\Models\Booking::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->whereNotNull('check_in_date')
                ->where('check_in_date', '<', $checkOut)
                ->where('check_out_date', '>', $checkIn)
                ->pluck('room_id');

            $unavailableRoomIds = $unavailableRoomIds->merge($nightIds);
        }

        // ── Per-slot / Per-hour: date (+ optional slot_id) ─────────────────
        if ($request->filled('date')) {
            $date   = $request->input('date');
            $slotId = $request->input('slot_id');

            $query = \App\Models\Booking::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('booking_date', $date)
                ->whereNotNull('time_slot_id');

            if ($slotId) {
                $query->where('time_slot_id', (int) $slotId);
            }

            $slotIds = $query->pluck('room_id');
            $unavailableRoomIds = $unavailableRoomIds->merge($slotIds);

            // Also flag rooms with overlapping per-night bookings on this date
            $nightIds = \App\Models\Booking::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->whereNotNull('check_in_date')
                ->where('check_in_date', '<=', $date)
                ->where('check_out_date', '>', $date)
                ->pluck('room_id');

            $unavailableRoomIds = $unavailableRoomIds->merge($nightIds);
        }

        return response()->json([
            'unavailable_room_ids' => $unavailableRoomIds->unique()->values()->map(fn($id) => (int) $id),
        ]);
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customers = Customer::orderBy('name')->get();

        // Show all non-maintenance rooms. Actual date-overlap availability is
        // checked via the AJAX availableRooms() endpoint when dates are selected,
        // so the static room status must NOT pre-filter per-night rooms here —
        // an "occupied" room today may be free for future dates.
        $rooms = Room::where('status', '!=', 'maintenance')
            ->orderBy('room_number')
            ->get();

        // Build per-hotel module flags so create form JS can gate sections
        // correctly even in superadmin "All Hotels" mode where rooms from
        // multiple hotels may appear in the same dropdown.
        $hotelModules = [];
        foreach ($rooms->pluck('hotel_id')->unique() as $hid) {
            $hotelModules[$hid] = [
                'slot'   => \App\Models\Module::withoutGlobalScopes()->where('hotel_id', $hid)->where('slug', 'time-slot-pricing')->where('is_enabled', true)->exists(),
                'hourly' => \App\Models\Module::withoutGlobalScopes()->where('hotel_id', $hid)->where('slug', 'hourly-pricing')->where('is_enabled', true)->exists(),
            ];
        }

        // Convenience flags for PHP-level @if guards (current hotel context)
        $slotModuleOn   = collect($hotelModules)->contains('slot', true);
        $hourlyModuleOn = collect($hotelModules)->contains('hourly', true);

        $timeSlots = $slotModuleOn  ? \App\Models\HotelTimeSlot::where('is_active', true)->ordered()->get() : collect();
        $addOns    = ($slotModuleOn || $hourlyModuleOn) ? \App\Models\RoomAddOn::active()->whereNull('room_id')->orderBy('name')->get() : collect();

        // Smart form layout flags
        $hasSlotRooms   = $rooms->contains(fn($r) => ($r->pricing_type ?? 'per_night') === 'per_slot');
        $hasNightRooms  = $rooms->contains(fn($r) => !in_array($r->pricing_type ?? 'per_night', ['per_slot', 'per_hour']));
        $hasHourlyRooms = $rooms->contains(fn($r) => ($r->pricing_type ?? 'per_night') === 'per_hour');
        // Pure slot: every room is slot-based and slot module is on
        $pureSlotHotel  = $hasSlotRooms && !$hasNightRooms && !$hasHourlyRooms && $slotModuleOn;
        // Mixed: more than one booking type is available
        $mixedBookingTypes = ((int)$hasNightRooms + (int)($hasSlotRooms && $slotModuleOn) + (int)($hasHourlyRooms && $hourlyModuleOn)) > 1;

        $bkSettings = \App\Models\Setting::first();
        $taxRate    = ($bkSettings && !empty($bkSettings->gst_number) && ($bkSettings->tax_rate ?? 0) > 0)
                        ? (float) $bkSettings->tax_rate : 0;

        return view('admin.bookings.create', compact(
            'customers', 'rooms', 'slotModuleOn', 'hourlyModuleOn', 'hotelModules', 'timeSlots', 'addOns',
            'pureSlotHotel', 'mixedBookingTypes', 'hasSlotRooms', 'hasNightRooms', 'hasHourlyRooms', 'taxRate'
        ));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        // ── Whole-hotel booking path ──────────────────────────────────────────
        if ($request->boolean('is_whole_hotel')) {
            return $this->storeWholeHotel($request);
        }

        // Support both single room_id and multi-select room_ids[]
        $roomIds = array_values(array_filter(array_map('intval',
            (array) ($request->input('room_ids') ?: ($request->input('room_id') ? [$request->input('room_id')] : []))
        )));
        if (empty($roomIds)) {
            return back()->withInput()->withErrors(['room_ids' => 'Please select at least one room.']);
        }
        $room         = Room::findOrFail($roomIds[0]);
        $pricingType  = $room->pricing_type ?? 'per_night';
        // Check modules using room's own hotel_id — bypasses HotelContext so
        // superadmin "All Hotels" mode doesn't accidentally force per_night.
        $slotModuleOn   = \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $room->hotel_id)
            ->where('slug', 'time-slot-pricing')
            ->where('is_enabled', true)
            ->exists();
        $hourlyModuleOn = \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $room->hotel_id)
            ->where('slug', 'hourly-pricing')
            ->where('is_enabled', true)
            ->exists();
        if ($pricingType === 'per_slot' && !$slotModuleOn)   $pricingType = 'per_night';
        if ($pricingType === 'per_hour' && !$hourlyModuleOn) $pricingType = 'per_night';

        // Validate based on pricing type
        $baseRules = [
            'customer_id'     => 'required|exists:customers,id',
            'adults'          => 'required|integer|min:1',
            'children'        => 'nullable|integer|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'special_requests'=> 'nullable|string',
        ];
        if ($pricingType === 'per_slot') {
            $rules = array_merge($baseRules, [
                'check_in_date'  => 'required|date',
                'check_out_date' => 'required|date|after_or_equal:check_in_date',
                'time_slot_id'   => 'required|exists:hotel_time_slots,id',
            ]);
        } elseif ($pricingType === 'per_hour') {
            $rules = array_merge($baseRules, [
                'booking_date'   => 'required|date',
                'slot_start_time'=> 'required|string|regex:/^\d{2}:\d{2}$/',
            ]);
        } else {
            $rules = array_merge($baseRules, [
                'check_in_date'  => 'required|date',
                'check_out_date' => 'required|date|after_or_equal:check_in_date',
            ]);
        }
        $validated = $request->validate($rules);

        // ── Per-slot overlap guard ──────────────────────────────────────────────
        if ($pricingType === 'per_slot') {
            $slotCheckIn = $validated['check_in_date'];
            $targetSlot = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
            $conflicting = (new \App\Services\SlotConflictService())->getConflictingRoomIds(
                $targetSlot,
                $slotCheckIn
            );
            if (in_array((int) $roomIds[0], $conflicting)) {
                return back()
                    ->withInput()
                    ->withErrors(['time_slot_id' => 'This room is already booked for an overlapping time slot on this date.']);
            }
            // Block if a whole-hotel per_night booking covers this date
            $whNightBlock = Booking::where('hotel_id', session('crm_hotel_id'))
                ->where('is_whole_hotel', true)
                ->where(function ($q) { $q->where('whole_hotel_pricing_type', 'per_night')->orWhereNull('whole_hotel_pricing_type'); })
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<=', $slotCheckIn)
                ->whereDate('check_out_date', '>=', $slotCheckIn)
                ->first();
            if ($whNightBlock) {
                return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whNightBlock->booking_number . '. Individual room bookings cannot be created for this date.']);
            }
            // Block if a whole-hotel per_slot booking overlaps this slot's time range on check-in day
            $whSlotBlocks = Booking::where('hotel_id', session('crm_hotel_id'))
                ->where('is_whole_hotel', true)
                ->where('whole_hotel_pricing_type', 'per_slot')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereNotNull('time_slot_id')
                ->whereDate('check_in_date', $slotCheckIn)
                ->with('timeSlot')
                ->get();
            foreach ($whSlotBlocks as $whSlotBook) {
                if ($whSlotBook->timeSlot && $this->slotsOverlap($targetSlot, $whSlotBook->timeSlot, $slotCheckIn)) {
                    return back()->withInput()->withErrors(['time_slot_id' => 'Blocked by whole-hotel slot reservation ' . $whSlotBook->booking_number . '. Individual room bookings cannot be created for this time slot.']);
                }
            }
            // Block if check_in_date falls on a LATER day of a multi-day whole-hotel per_slot booking
            $whSlotLaterDay = Booking::where('hotel_id', session('crm_hotel_id'))
                ->where('is_whole_hotel', true)
                ->where('whole_hotel_pricing_type', 'per_slot')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<', $slotCheckIn)
                ->whereDate('check_out_date', '>=', $slotCheckIn)
                ->first();
            if ($whSlotLaterDay) {
                return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whSlotLaterDay->booking_number . '. The property is fully occupied on this date.']);
            }
        }

        // ── Per-hour whole-hotel conflict guard ────────────────────────────────
        if ($pricingType === 'per_hour') {
            $whBlock = Booking::where('hotel_id', session('crm_hotel_id'))
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<=', $validated['booking_date'])
                ->whereDate('check_out_date', '>=', $validated['booking_date'])
                ->first();
            if ($whBlock) {
                return back()->withInput()->withErrors(['booking_date' => 'Blocked by whole-hotel reservation ' . $whBlock->booking_number . '. Individual room bookings cannot be created for this date.']);
            }
        }

        // ── Per-night whole-hotel conflict guard ───────────────────────────────
        if ($pricingType === 'per_night') {
            $ciDate = $validated['check_in_date'];
            $coDate = $validated['check_out_date'];
            $whBlock = Booking::where('hotel_id', session('crm_hotel_id'))
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->first();
            if ($whBlock) {
                return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whBlock->booking_number . '. Individual room bookings cannot be created for this period.']);
            }

            // ── Per-night room overlap guard (prevents double-booking) ──────────
            // Check every requested room for overlapping confirmed/checked_in bookings.
            $conflictingBookings = Booking::whereIn('room_id', $roomIds)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->with('room')
                ->get();

            if ($conflictingBookings->isNotEmpty()) {
                $msgs = $conflictingBookings->map(function ($b) {
                    $roomNum = $b->room?->room_number ?? $b->room_id;
                    return "Room {$roomNum} is already booked for these dates (Booking #{$b->booking_number})";
                })->unique()->implode('; ');
                return back()->withInput()->withErrors(['room_ids' => $msgs]);
            }
        }

        $bookingPrefix   = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
        $bookingNumber   = $bookingPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));
        $extraRoomTotals = []; // populated in per_night block; used by group-booking loop below

        if ($pricingType === 'per_slot') {
            $slot          = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
            // Pre-calculate per-room slot amounts (mirrors per_night extraRoomTotals pattern)
            $extraRoomTotals = [];
            foreach (array_slice($roomIds, 1) as $extraRoomId) {
                $extraRoomTotals[(int) $extraRoomId] = $slot->base_price;
            }
            // Total = slot price × number of rooms (not just 1 room)
            $totalAmount   = $slot->base_price * count($roomIds);
            $advancePayment= $validated['advance_payment'] ?? 0;
            $slotNights    = max(0, Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date'])));
            $bookingData   = [
                'booking_number'  => $bookingNumber,
                'hotel_id'        => session('crm_hotel_id'),
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $roomIds[0],
                'check_in_date'   => $validated['check_in_date'],
                'check_out_date'  => $validated['check_out_date'],
                'booking_date'    => $validated['check_in_date'],
                'time_slot_id'    => $validated['time_slot_id'],
                'nights'          => $slotNights,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'total_amount'    => $totalAmount,
                'advance_payment' => $advancePayment,
                'balance_due'     => $totalAmount - $advancePayment,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => 'confirmed',
                'payment_status'  => $advancePayment >= $totalAmount ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'meal_breakfast'  => false, 'meal_lunch' => false, 'meal_dinner' => false,
                'meal_cost' => 0, 'extra_beds' => 0, 'extra_bed_cost' => 0,
            ];
        } elseif ($pricingType === 'per_hour') {
            // total_amount is 0 at booking time — calculated at checkout using actual elapsed hours
            $advancePayment= 0;
            $bookingData   = [
                'booking_number'  => $bookingNumber,
                'hotel_id'        => session('crm_hotel_id'),
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $roomIds[0],
                'check_in_date'   => $validated['booking_date'],
                'check_out_date'  => $validated['booking_date'],
                'booking_date'    => $validated['booking_date'],
                'slot_start_time' => $validated['slot_start_time'],
                'hours_booked'    => null,
                'nights'          => 0,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'total_amount'    => 0,
                'advance_payment' => 0,
                'balance_due'     => 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => 'confirmed',
                'payment_status'  => 'pending',
                'meal_breakfast'  => false, 'meal_lunch' => false, 'meal_dinner' => false,
                'meal_cost' => 0, 'extra_beds' => 0, 'extra_bed_cost' => 0,
            ];
        } else {
            // per_night — same-day checkout counts as 1 night minimum
            $nights        = max(1, Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date'])));
            $mealBreakfast = $request->boolean('meal_breakfast') && $room->has_breakfast;
            $mealLunch     = $request->boolean('meal_lunch')     && $room->has_lunch;
            $mealDinner    = $request->boolean('meal_dinner')    && $room->has_dinner;
            $mealCost      = ($mealBreakfast ? ($room->breakfast_price * $nights) : 0)
                           + ($mealLunch     ? ($room->lunch_price     * $nights) : 0)
                           + ($mealDinner    ? ($room->dinner_price    * $nights) : 0);
            $extraBeds     = $room->has_extra_bed ? max(0, (int) $request->input('extra_beds', 0)) : 0;
            $extraBedCost  = $room->has_extra_bed ? ($extraBeds * ($room->extra_bed_price ?? 0) * $nights) : 0;
            $primaryRoomTotal = $nights * $room->price_per_night + $mealCost + $extraBedCost;

            // Pre-calculate each extra room's individual amount so we can sum into one combined total
            $extraRoomTotals = [];
            foreach (array_slice($roomIds, 1) as $extraRoomId) {
                $extraRoom = Room::find((int) $extraRoomId);
                if ($extraRoom) {
                    $extraRoomTotals[(int) $extraRoomId] = $nights * ($extraRoom->price_per_night ?? 0);
                }
            }
            $totalAmount   = $primaryRoomTotal + array_sum($extraRoomTotals);

            $advancePayment= $validated['advance_payment'] ?? 0;
            $bookingData   = [
                'booking_number'  => $bookingNumber,
                'hotel_id'        => session('crm_hotel_id'),
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $roomIds[0],
                'check_in_date'   => $validated['check_in_date'],
                'check_out_date'  => $validated['check_out_date'],
                'nights'          => $nights,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'total_amount'    => $totalAmount,
                'advance_payment' => $advancePayment,
                'balance_due'     => $totalAmount - $advancePayment,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => 'confirmed',
                'payment_status'  => $advancePayment >= $totalAmount ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'meal_breakfast'  => $mealBreakfast,
                'meal_lunch'      => $mealLunch,
                'meal_dinner'     => $mealDinner,
                'meal_cost'       => $mealCost,
                'extra_beds'      => $extraBeds,
                'extra_bed_cost'  => $extraBedCost,
            ];
        }

        // ── Custom price override (single-room only; skip for multi-room group bookings) ────
        // For group bookings the front-end custom_total only captures the first room's
        // calculated amount, which will differ from the correct server-side combined total,
        // falsely triggering the override and wiping out the other rooms' amounts.
        $customTotal     = (float) $request->input('custom_total', 0);
        $calculatedTotal = $bookingData['total_amount'];
        $advAmt          = (float) ($bookingData['advance_payment'] ?? 0);
        $isGroupBooking  = count($roomIds) > 1;
        if (!$isGroupBooking && $customTotal > 0 && abs($customTotal - $calculatedTotal) > 0.01) {
            $bookingData['total_amount']     = $customTotal;
            $bookingData['balance_due']      = max(0, $customTotal - $advAmt);
            $bookingData['payment_status']   = $advAmt >= $customTotal ? 'paid' : ($advAmt > 0 ? 'partial' : 'pending');
            $bookingData['price_overridden'] = true;
        } else {
            $bookingData['price_overridden'] = false;
        }

        $booking = Booking::create($bookingData);

        // Save add-ons for slot/hourly bookings
        if (in_array($pricingType, ['per_slot', 'per_hour']) && $request->filled('addon_ids')) {
            $addOnTotal = 0;
            foreach ($request->input('addon_ids', []) as $aoId) {
                $ao = \App\Models\RoomAddOn::find($aoId);
                if ($ao) {
                    \App\Models\BookingAddOn::create(['booking_id' => $booking->id, 'room_add_on_id' => $ao->id, 'name' => $ao->name, 'price' => $ao->price]);
                    $addOnTotal += $ao->price;
                }
            }
            if ($addOnTotal > 0) {
                $booking->increment('total_amount', $addOnTotal);
                $booking->increment('balance_due', $addOnTotal);
            }
        }

        $advancePayment = $bookingData['advance_payment'] ?? 0;
        if ($advancePayment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $bookingData['customer_id'],
                'amount'         => $advancePayment,
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Advance at booking',
                'transaction_id' => $bookingPrefix . '-TXN-' . strtoupper(substr(uniqid(), -8)),
            ]);
        }

        // Only mark room occupied for per_night (slot rooms can be booked multiple times/day)
        if ($pricingType === 'per_night') {
            $room->update(['status' => 'occupied']);
        }

        // ── Group booking: link extra rooms to primary via group_booking_id ──────
        // All payment/billing is on the primary booking. Child bookings exist only
        // to track room occupancy and check-in/out state for each additional room.
        $allNumbers = [$booking->booking_number];
        if (count($roomIds) > 1) {
            foreach (array_slice($roomIds, 1) as $extraRoomId) {
                $extraRoom = Room::find((int) $extraRoomId);
                if (!$extraRoom) continue;
                $extraNum  = $bookingPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));
                $extraData = $bookingData;
                $extraData['group_booking_id'] = $booking->id;  // link to primary
                $extraData['booking_number']   = $extraNum;
                $extraData['room_id']          = (int) $extraRoomId;
                $extraData['advance_payment']  = 0;             // all payment on primary
                $extraData['price_overridden'] = false;
                // Store per-room amount for display in the rooms breakdown
                // per_night and per_slot both populate $extraRoomTotals; per_hour stays 0
                $extraData['total_amount']     = $extraRoomTotals[(int) $extraRoomId] ?? 0;
                $extraData['balance_due']      = 0;             // billing handled by primary
                $extraData['payment_status']   = 'pending';
                $extraBooking = Booking::create($extraData);
                $allNumbers[] = $extraBooking->booking_number;
                if ($pricingType === 'per_night') {
                    $extraRoom->update(['status' => 'occupied']);
                }
            }
        }

        $customer = Customer::find($bookingData['customer_id']);
        $roomLabel = count($roomIds) > 1 ? count($roomIds) . ' rooms' : 'Room ' . $room->room_number;
        ActivityLogger::log('Created', 'Booking', 'Booking #' . $booking->booking_number . ' created for ' . ($customer->name ?? 'guest') . ' — ' . $roomLabel . ' (' . $pricingType . ')');
        WhatsAppService::sendForEvent('booking.created', $booking);
        // Only send the self-checkin link template when QR check-in is enabled for this hotel.
        $hotelSettingsForWa = \App\Models\Setting::where('hotel_id', $booking->hotel_id)->first();
        if (!$hotelSettingsForWa || $hotelSettingsForWa->qr_checkin_enabled !== false) {
            WhatsAppService::sendForEvent('booking.details_request', $booking);
        }
        WhatsAppService::sendOwnerAlert($booking);
        $successMsg = count($allNumbers) > 1
            ? 'Group booking created for ' . count($allNumbers) . ' rooms! #' . $booking->booking_number
            : 'Booking created! #' . $booking->booking_number;
        return redirect()->route('bookings.show', $booking->id)->with('success', $successMsg);
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['customer', 'room', 'payments', 'invoice', 'bookingGuests', 'timeSlot', 'bookingAddOns', 'extraCharges', 'paymentReferences', 'groupedBookings.room'])->findOrFail($id);
        $rooms   = Room::where('status', '!=', 'maintenance')->orderBy('room_number')->get();
        return view('admin.bookings.show', compact('booking', 'rooms'));
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking  = Booking::findOrFail($id);
        $customers = Customer::orderBy('name')->get();

        // Use the booking's own room hotel_id — bypasses HotelContext so
        // superadmin scenarios resolve the correct module state.
        // For whole-hotel bookings, room_id is null — derive hotel context from booking.hotel_id
        $bookingHotelId = $booking->room?->hotel_id ?? $booking->hotel_id;
        $slotModuleOn   = $bookingHotelId ? \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $bookingHotelId)->where('slug', 'time-slot-pricing')->where('is_enabled', true)->exists() : false;
        $hourlyModuleOn = $bookingHotelId ? \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $bookingHotelId)->where('slug', 'hourly-pricing')->where('is_enabled', true)->exists() : false;

        // Only show rooms of the same pricing_type to keep pricing context stable.
        $pricingType = $booking->is_whole_hotel
            ? ($booking->whole_hotel_pricing_type ?? 'per_night')
            : ($booking->room?->pricing_type ?? 'per_night');
        $rooms = Room::where('pricing_type', $pricingType)->orderBy('room_number')->get();

        $timeSlots = $slotModuleOn ? \App\Models\HotelTimeSlot::where('is_active', true)->ordered()->get() : collect();
        return view('admin.bookings.edit', compact('booking', 'customers', 'rooms', 'slotModuleOn', 'hourlyModuleOn', 'timeSlots'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::findOrFail($id);

        // ── Whole-hotel booking update path ───────────────────────────────────
        if ($booking->is_whole_hotel || $request->boolean('is_whole_hotel')) {
            return $this->updateWholeHotel($request, $booking);
        }

        $oldRoomId   = $booking->room_id;
        $newRoomId   = (int) $request->input('room_id', $oldRoomId);
        $room        = Room::findOrFail($newRoomId);
        $pricingType = $room->pricing_type ?? 'per_night';

        // Module gating — use room's hotel to bypass HotelContext in superadmin mode
        $slotModuleOn = \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $room->hotel_id)
            ->where('slug', 'time-slot-pricing')
            ->where('is_enabled', true)
            ->exists();
        $hourlyModuleOn = \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $room->hotel_id)
            ->where('slug', 'hourly-pricing')
            ->where('is_enabled', true)
            ->exists();
        if ($pricingType === 'per_slot' && !$slotModuleOn)   $pricingType = 'per_night';
        if ($pricingType === 'per_hour' && !$hourlyModuleOn) $pricingType = 'per_night';

        $baseRules = [
            'room_id'         => 'required|exists:rooms,id',
            'adults'          => 'required|integer|min:1',
            'children'        => 'nullable|integer|min:0',
            'special_requests'=> 'nullable|string',
            'status'          => 'required|in:confirmed,checked_in,checked_out,cancelled',
            'advance_payment' => 'nullable|numeric|min:0',
        ];

        if ($pricingType === 'per_slot') {
            $rules = array_merge($baseRules, [
                'booking_date' => 'required|date',
                'time_slot_id' => 'required|exists:hotel_time_slots,id',
            ]);
        } elseif ($pricingType === 'per_hour') {
            $rules = array_merge($baseRules, [
                'booking_date'    => 'required|date',
                'slot_start_time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            ]);
        } else {
            $rules = array_merge($baseRules, [
                'check_in_date'  => 'required|date',
                'check_out_date' => 'required|date|after_or_equal:check_in_date',
            ]);
        }

        $validated = $request->validate($rules);
        $advancePayment = (float) ($validated['advance_payment'] ?? $booking->advance_payment ?? 0);

        // ── Whole-hotel conflict guards on individual-room update ───────────────
        $updateStatus = $validated['status'] ?? $booking->status;
        if (!in_array($updateStatus, ['cancelled', 'checked_out'])) {
            if ($pricingType === 'per_slot') {
                $whBlock = Booking::where('hotel_id', $room->hotel_id)
                    ->where('id', '!=', $booking->id)
                    ->where('is_whole_hotel', true)
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->whereDate('check_in_date', '<=', $validated['booking_date'])
                    ->whereDate('check_out_date', '>=', $validated['booking_date'])
                    ->first();
                if ($whBlock) {
                    return back()->withInput()->withErrors(['booking_date' => 'Blocked by whole-hotel reservation ' . $whBlock->booking_number . '. Individual room bookings cannot overlap this date.']);
                }
            } elseif ($pricingType === 'per_hour') {
                $whBlock = Booking::where('hotel_id', $room->hotel_id)
                    ->where('id', '!=', $booking->id)
                    ->where('is_whole_hotel', true)
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->whereDate('check_in_date', '<=', $validated['booking_date'])
                    ->whereDate('check_out_date', '>=', $validated['booking_date'])
                    ->first();
                if ($whBlock) {
                    return back()->withInput()->withErrors(['booking_date' => 'Blocked by whole-hotel reservation ' . $whBlock->booking_number . '. Individual room bookings cannot overlap this date.']);
                }
            } elseif ($pricingType === 'per_night') {
                $ciDate  = $validated['check_in_date'];
                $coDate  = $validated['check_out_date'];
                $whBlock = Booking::where('hotel_id', $room->hotel_id)
                    ->where('id', '!=', $booking->id)
                    ->where('is_whole_hotel', true)
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->where('check_in_date', '<', $coDate)
                    ->where('check_out_date', '>', $ciDate)
                    ->first();
                if ($whBlock) {
                    return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whBlock->booking_number . '. Individual room bookings cannot overlap this period.']);
                }
            }
        }

        if ($pricingType === 'per_slot') {
            $slot        = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
            $totalAmount = $slot->base_price;
            $updateData  = [
                'room_id'         => $newRoomId,
                'booking_date'    => $validated['booking_date'],
                'check_in_date'   => $validated['booking_date'],
                'check_out_date'  => $validated['booking_date'],
                'time_slot_id'    => $validated['time_slot_id'],
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => $validated['status'],
                'total_amount'    => $totalAmount,
                'advance_payment' => $advancePayment,
                'balance_due'     => max(0, $totalAmount - $advancePayment),
                'payment_status'  => $advancePayment >= $totalAmount ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
            ];
        } elseif ($pricingType === 'per_hour') {
            // total_amount kept at 0 — recalculated at checkout using actual elapsed hours
            $updateData  = [
                'room_id'         => $newRoomId,
                'booking_date'    => $validated['booking_date'],
                'check_in_date'   => $validated['booking_date'],
                'check_out_date'  => $validated['booking_date'],
                'slot_start_time' => $validated['slot_start_time'],
                'hours_booked'    => null,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => $validated['status'],
                'total_amount'    => 0,
                'advance_payment' => 0,
                'balance_due'     => 0,
                'payment_status'  => 'pending',
            ];
        } else {
            // per_night — same-day checkout counts as 1 night minimum
            $nights        = max(1, Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date'])));
            $mealBreakfast = $request->boolean('meal_breakfast') && $room->has_breakfast;
            $mealLunch     = $request->boolean('meal_lunch')     && $room->has_lunch;
            $mealDinner    = $request->boolean('meal_dinner')    && $room->has_dinner;
            $mealCost      = ($mealBreakfast ? ($room->breakfast_price * $nights) : 0)
                           + ($mealLunch     ? ($room->lunch_price     * $nights) : 0)
                           + ($mealDinner    ? ($room->dinner_price    * $nights) : 0);
            $extraBeds     = ($room->has_extra_bed) ? max(0, (int) $request->input('extra_beds', 0)) : 0;
            $extraBedCost  = $room->has_extra_bed ? ($extraBeds * ($room->extra_bed_price ?? 0) * $nights) : 0;
            $total         = $nights * $room->price_per_night + $mealCost + $extraBedCost;
            $updateData    = array_merge($validated, [
                'room_id'         => $newRoomId,
                'nights'          => $nights,
                'total_amount'    => $total,
                'advance_payment' => $advancePayment,
                'balance_due'     => max(0, $total - $advancePayment),
                'meal_breakfast'  => $mealBreakfast,
                'meal_lunch'      => $mealLunch,
                'meal_dinner'     => $mealDinner,
                'meal_cost'       => $mealCost,
                'extra_beds'      => $extraBeds,
                'extra_bed_cost'  => $extraBedCost,
            ]);
        }

        // ── Custom price override (all pricing types including per_hour) ────
        $customTotal     = (float) $request->input('custom_total', 0);
        $calculatedTotal = $updateData['total_amount'];
        $advAmt          = (float) ($updateData['advance_payment'] ?? $advancePayment);
        if ($customTotal > 0 && abs($customTotal - $calculatedTotal) > 0.01) {
            // A new custom price was explicitly submitted
            $updateData['total_amount']     = $customTotal;
            $updateData['balance_due']      = max(0, $customTotal - $advAmt);
            $updateData['payment_status']   = $advAmt >= $customTotal ? 'paid' : ($advAmt > 0 ? 'partial' : 'pending');
            $updateData['price_overridden'] = true;
        } elseif ($customTotal <= 0 && $booking->price_overridden) {
            // No new custom total submitted but booking already has a custom price — preserve it exactly
            $existingTotal = (float) $booking->total_amount;
            $updateData['total_amount']     = $existingTotal;
            $updateData['balance_due']      = max(0, $existingTotal - $advAmt);
            $updateData['payment_status']   = $advAmt >= $existingTotal ? 'paid' : ($advAmt > 0 ? 'partial' : 'pending');
            $updateData['price_overridden'] = true;
        } else {
            $updateData['price_overridden'] = false;
        }

        $booking->update($updateData);
        $newStatus = $validated['status'];

        // OTA conflict resolution: when an admin assigns/changes a room on a
        // booking that came in with ota_conflict=true, mark the conflict cleared
        // and resolve any open ota_booking_conflicts row.
        if ($booking->ota_conflict && $newRoomId) {
            $booking->update(['ota_conflict' => false]);
            \App\Models\OtaBookingConflict::where('booking_id', $booking->id)
                ->where('resolved', false)
                ->update([
                    'resolved'    => true,
                    'resolved_at' => now(),
                ]);
        }

        // Room occupancy: only track for per_night rooms
        if ($oldRoomId !== $newRoomId) {
            $oldRoom = Room::find($oldRoomId);
            if ($oldRoom && ($oldRoom->pricing_type ?? 'per_night') === 'per_night') {
                $oldRoom->update(['status' => 'available']);
            }
            if ($pricingType === 'per_night' && in_array($newStatus, ['confirmed', 'checked_in'])) {
                $room->update(['status' => 'occupied']);
            }
        } else {
            if ($pricingType === 'per_night') {
                if (in_array($newStatus, ['cancelled', 'checked_out'])) {
                    $room->update(['status' => 'available']);
                } elseif (in_array($newStatus, ['confirmed', 'checked_in'])) {
                    $room->update(['status' => 'occupied']);
                }
            }
        }

        ActivityLogger::log('Updated', 'Booking', 'Updated booking #' . $booking->booking_number . ' (' . $pricingType . ')');
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Booking updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['room', 'groupedBookings.room'])->findOrFail($id);
        $number  = $booking->booking_number;

        // Cancel all child bookings in a group booking and free their rooms
        foreach ($booking->groupedBookings as $childBooking) {
            $childBooking->update(['status' => 'cancelled']);
            if ($childBooking->room) {
                $childBooking->room->update(['status' => 'available']);
            }
        }

        $booking->update(['status' => 'cancelled']);
        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }
        ActivityLogger::log('Deleted', 'Booking', 'Cancelled booking #' . $number);
        return redirect()->route('bookings.index')->with('success', 'Booking cancelled.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WHOLE-HOTEL BOOKING HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    protected function storeWholeHotel(Request $request)
    {
        $hotelId = session('crm_hotel_id');

        $slotModuleOn = \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('slug', 'time-slot-pricing')
            ->where('is_enabled', true)
            ->exists();

        $whPricingType = $request->input('whole_hotel_pricing_type', 'per_night');
        if (!$slotModuleOn) $whPricingType = 'per_night';

        $allRooms      = Room::where('hotel_id', $hotelId)->where('status', '!=', 'maintenance')->get();
        $bookingPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
        $bookingNumber = $bookingPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));

        // ── Per-Slot whole-hotel path ──────────────────────────────────────────
        if ($whPricingType === 'per_slot') {
            $validated = $request->validate([
                'customer_id'     => 'required|exists:customers,id',
                'check_in_date'   => 'required|date',
                'check_out_date'  => 'required|date|after_or_equal:check_in_date',
                'time_slot_id'    => 'nullable|exists:hotel_time_slots,id',
                'adults'          => 'required|integer|min:1',
                'children'        => 'nullable|integer|min:0',
                'custom_total'    => 'required|numeric|min:1',
                'advance_payment' => 'nullable|numeric|min:0',
                'special_requests'=> 'nullable|string',
            ]);

            $ciDate = $validated['check_in_date'];
            $coDate = $validated['check_out_date'];
            $nights = max(0, Carbon::parse($ciDate)->diffInDays(Carbon::parse($coDate)));

            // Conflict: per-night room bookings in this date range
            $roomPerNightConflicts = Booking::where('hotel_id', $hotelId)
                ->whereNotNull('room_id')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->with('room:id,room_number')
                ->get();
            if ($roomPerNightConflicts->isNotEmpty()) {
                $roomList = $roomPerNightConflicts->pluck('room.room_number')->filter()->unique()->sort()->implode(', ');
                return back()->withInput()->withErrors(['check_in_date' => 'Room(s) ' . $roomList . ' have bookings in this date range. Check them out or cancel first.']);
            }

            // Conflict on check-in day: slot-based room bookings that overlap the arrival slot
            $targetSlot = null;
            if (!empty($validated['time_slot_id'])) {
                $targetSlot = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
                $conflictingRoomIds = (new \App\Services\SlotConflictService())->getConflictingRoomIds($targetSlot, $ciDate);
                if (!empty($conflictingRoomIds)) {
                    $conflictRooms = Room::whereIn('id', $conflictingRoomIds)->pluck('room_number')->sort()->implode(', ');
                    return back()->withInput()->withErrors(['time_slot_id' => 'Room(s) ' . $conflictRooms . ' have overlapping slot bookings on the check-in date.']);
                }
                // Block if another whole-hotel per_slot booking overlaps on check-in day
                $whSlotConflict = Booking::where('hotel_id', $hotelId)
                    ->where('is_whole_hotel', true)
                    ->where('whole_hotel_pricing_type', 'per_slot')
                    ->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->whereDate('check_in_date', $ciDate)
                    ->whereNotNull('time_slot_id')
                    ->with('timeSlot')
                    ->get()
                    ->first(fn($b) => $b->timeSlot && $this->slotsOverlap($targetSlot, $b->timeSlot, $ciDate));
                if ($whSlotConflict) {
                    return back()->withInput()->withErrors(['time_slot_id' => 'Another whole-hotel booking (' . $whSlotConflict->booking_number . ') has an overlapping arrival slot on the check-in date.']);
                }
            }

            // Conflict: another whole-hotel booking whose date range overlaps this one
            $whConflict = Booking::where('hotel_id', $hotelId)
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->first();
            if ($whConflict) {
                return back()->withInput()->withErrors(['check_in_date' => 'Another whole-hotel booking (' . $whConflict->booking_number . ') already covers part of this date range.']);
            }

            $advancePayment = (float) ($validated['advance_payment'] ?? 0);
            $customTotal    = (float) $validated['custom_total'];

            $bookingData = [
                'booking_number'          => $bookingNumber,
                'hotel_id'                => $hotelId,
                'customer_id'             => $validated['customer_id'],
                'room_id'                 => null,
                'check_in_date'           => $ciDate,
                'check_out_date'          => $coDate,
                'booking_date'            => $ciDate,
                'time_slot_id'            => $validated['time_slot_id'] ?: null,
                'nights'                  => $nights,
                'adults'                  => $validated['adults'],
                'children'                => $validated['children'] ?? 0,
                'total_amount'            => $customTotal,
                'advance_payment'         => $advancePayment,
                'balance_due'             => max(0, $customTotal - $advancePayment),
                'special_requests'        => $validated['special_requests'] ?? null,
                'status'                  => 'confirmed',
                'payment_status'          => $advancePayment >= $customTotal ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'is_whole_hotel'          => true,
                'whole_hotel_pricing_type'=> 'per_slot',
                'price_overridden'        => true,
                'meal_breakfast' => false, 'meal_lunch' => false, 'meal_dinner' => false,
                'meal_cost' => 0, 'extra_beds' => 0, 'extra_bed_cost' => 0,
            ];

            $booking = Booking::create($bookingData);

            if ($advancePayment > 0) {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'customer_id'    => $bookingData['customer_id'],
                    'amount'         => $advancePayment,
                    'payment_method' => $request->input('payment_method', 'cash'),
                    'payment_type'   => 'advance',
                    'status'         => 'completed',
                    'notes'          => 'Advance at booking',
                    'transaction_id' => $bookingPrefix . '-TXN-' . strtoupper(substr(uniqid(), -8)),
                ]);
            }

            $slotLabel = $targetSlot ? ' — ' . $targetSlot->name . ' slot' : '';
            $customer  = Customer::find($bookingData['customer_id']);
            ActivityLogger::log('Created', 'Booking', 'Booking #' . $booking->booking_number . ' created for ' . ($customer->name ?? 'guest') . ' — Whole Hotel / Villa' . $slotLabel . ' (' . $allRooms->count() . ' rooms)');
            WhatsAppService::sendForEvent('booking.created', $booking);
            $hotelSettingsForWa = \App\Models\Setting::where('hotel_id', $booking->hotel_id)->first();
            if (!$hotelSettingsForWa || $hotelSettingsForWa->qr_checkin_enabled !== false) {
                WhatsAppService::sendForEvent('booking.details_request', $booking);
            }
            WhatsAppService::sendOwnerAlert($booking);
            return redirect()->route('bookings.show', $booking->id)->with('success', 'Whole-Hotel booking created! #' . $booking->booking_number);
        }

        // ── Per-Night whole-hotel path (existing logic) ────────────────────────
        $validated = $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'check_in_date'   => 'required|date',
            'check_out_date'  => 'required|date|after_or_equal:check_in_date',
            'adults'          => 'required|integer|min:1',
            'children'        => 'nullable|integer|min:0',
            'custom_total'    => 'required|numeric|min:1',
            'advance_payment' => 'nullable|numeric|min:0',
            'special_requests'=> 'nullable|string',
        ]);

        // ── Conflict guard ─────────────────────────────────────────────────────
        $ciDate = $validated['check_in_date'];
        $coDate = $validated['check_out_date'];
        $roomConflicts = Booking::where('hotel_id', $hotelId)
            ->whereNotNull('room_id')
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where(function ($q) use ($ciDate, $coDate) {
                $q->where(function ($q2) use ($ciDate, $coDate) {
                    $q2->where('check_in_date', '<', $coDate)->where('check_out_date', '>', $ciDate);
                })->orWhere(function ($q2) use ($ciDate, $coDate) {
                    $q2->whereDate('booking_date', '>=', $ciDate)->whereDate('booking_date', '<=', $coDate)->whereNotNull('booking_date');
                });
            })
            ->with('room:id,room_number')
            ->get();
        $whConflict = Booking::where('hotel_id', $hotelId)
            ->where('is_whole_hotel', true)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<', $coDate)
            ->where('check_out_date', '>', $ciDate)
            ->first();
        if ($whConflict) {
            return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whConflict->booking_number . '. Cannot create another whole-hotel booking for this period.']);
        }
        if ($roomConflicts->count() > 0) {
            $roomList = $roomConflicts->pluck('room.room_number')->filter()->unique()->sort()->implode(', ');
            return back()->withInput()->withErrors(['check_in_date' => 'Rooms already have bookings in this period: ' . ($roomList ?: 'some rooms') . '. Check out or cancel those bookings first.']);
        }

        $advancePayment = (float) ($validated['advance_payment'] ?? 0);
        $customTotal    = (float) $validated['custom_total'];
        $nights         = max(1, Carbon::parse($ciDate)->diffInDays(Carbon::parse($coDate)));

        $bookingData = [
            'booking_number'          => $bookingNumber,
            'hotel_id'                => $hotelId,
            'customer_id'             => $validated['customer_id'],
            'room_id'                 => null,
            'check_in_date'           => $ciDate,
            'check_out_date'          => $coDate,
            'nights'                  => $nights,
            'adults'                  => $validated['adults'],
            'children'                => $validated['children'] ?? 0,
            'total_amount'            => $customTotal,
            'advance_payment'         => $advancePayment,
            'balance_due'             => max(0, $customTotal - $advancePayment),
            'special_requests'        => $validated['special_requests'] ?? null,
            'status'                  => 'confirmed',
            'payment_status'          => $advancePayment >= $customTotal ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
            'is_whole_hotel'          => true,
            'whole_hotel_pricing_type'=> 'per_night',
            'price_overridden'        => true,
            'meal_breakfast' => false, 'meal_lunch' => false, 'meal_dinner' => false,
            'meal_cost' => 0, 'extra_beds' => 0, 'extra_bed_cost' => 0,
        ];

        $booking = Booking::create($bookingData);

        if ($advancePayment > 0) {
            Payment::create([
                'booking_id'     => $booking->id,
                'customer_id'    => $bookingData['customer_id'],
                'amount'         => $advancePayment,
                'payment_method' => $request->input('payment_method', 'cash'),
                'payment_type'   => 'advance',
                'status'         => 'completed',
                'notes'          => 'Advance at booking',
                'transaction_id' => $bookingPrefix . '-TXN-' . strtoupper(substr(uniqid(), -8)),
            ]);
        }

        $customer = Customer::find($bookingData['customer_id']);
        ActivityLogger::log('Created', 'Booking', 'Booking #' . $booking->booking_number . ' created for ' . ($customer->name ?? 'guest') . ' — Whole Hotel / Villa (' . $allRooms->count() . ' rooms)');
        WhatsAppService::sendForEvent('booking.created', $booking);
        $hotelSettingsForWa = \App\Models\Setting::where('hotel_id', $booking->hotel_id)->first();
        if (!$hotelSettingsForWa || $hotelSettingsForWa->qr_checkin_enabled !== false) {
            WhatsAppService::sendForEvent('booking.details_request', $booking);
        }
        WhatsAppService::sendOwnerAlert($booking);
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Whole-Hotel booking created! #' . $booking->booking_number);
    }

    /**
     * Returns true if slotA and slotB overlap in time on the given date.
     * Handles overnight slots that cross midnight.
     */
    private function slotsOverlap(\App\Models\HotelTimeSlot $slotA, \App\Models\HotelTimeSlot $slotB, string $date): bool
    {
        $aStart = Carbon::parse($date . ' ' . $slotA->start_time);
        $aEnd   = Carbon::parse($date . ' ' . $slotA->end_time);
        if ($slotA->is_overnight || $aEnd <= $aStart) $aEnd->addDay();

        $bStart = Carbon::parse($date . ' ' . $slotB->start_time);
        $bEnd   = Carbon::parse($date . ' ' . $slotB->end_time);
        if ($slotB->is_overnight || $bEnd <= $bStart) $bEnd->addDay();

        return $aStart <= $bEnd && $bStart <= $aEnd;
    }

    protected function updateWholeHotel(Request $request, Booking $booking)
    {
        $hotelId       = session('crm_hotel_id');
        $whPricingType = $booking->whole_hotel_pricing_type ?? $request->input('whole_hotel_pricing_type', 'per_night');

        $rules = [
            'customer_id'     => 'required|exists:customers,id',
            'adults'          => 'required|integer|min:1',
            'children'        => 'nullable|integer|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'special_requests'=> 'nullable|string',
            'status'          => 'required|in:confirmed,checked_in,checked_out,cancelled',
        ];
        if ($whPricingType === 'per_night') {
            $rules['check_in_date']  = 'required|date';
            $rules['check_out_date'] = 'required|date|after_or_equal:check_in_date';
        } elseif ($whPricingType === 'per_slot') {
            $rules['check_in_date']  = 'required|date';
            $rules['check_out_date'] = 'required|date|after_or_equal:check_in_date';
            $rules['time_slot_id']   = 'nullable|exists:hotel_time_slots,id';
        } else {
            $rules['booking_date'] = 'required|date';
        }
        $validated = $request->validate($rules);

        // ── Conflict checks on update (excluding self) — all pricing types ────────
        if ($whPricingType === 'per_night' && !in_array($validated['status'], ['cancelled', 'checked_out'])) {
            $ciDate = $validated['check_in_date'];
            $coDate = $validated['check_out_date'];
            $roomConflicts = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->whereNotNull('room_id')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->with('room:id,room_number')
                ->get();
            $whConflict = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->first();
            if ($whConflict) {
                return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whConflict->booking_number . '. Dates overlap with an existing whole-hotel booking.']);
            }
            if ($roomConflicts->count() > 0) {
                $roomList = $roomConflicts->pluck('room.room_number')->filter()->unique()->sort()->implode(', ');
                return back()->withInput()->withErrors(['check_in_date' => 'Rooms already booked in this period: ' . ($roomList ?: 'some rooms') . '. Cannot change dates to overlap with them.']);
            }
        } elseif ($whPricingType === 'per_slot' && !in_array($validated['status'] ?? $booking->status, ['cancelled', 'checked_out'])) {
            $ciDate = $validated['check_in_date'];
            $coDate = $validated['check_out_date'];
            $roomConflicts = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->whereNotNull('room_id')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->with('room:id,room_number')
                ->get();
            $whConflict = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->where('check_in_date', '<', $coDate)
                ->where('check_out_date', '>', $ciDate)
                ->first();
            if ($whConflict) {
                return back()->withInput()->withErrors(['check_in_date' => 'Blocked by whole-hotel reservation ' . $whConflict->booking_number . '. Dates overlap with an existing whole-hotel booking.']);
            }
            if ($roomConflicts->count() > 0) {
                $roomList = $roomConflicts->pluck('room.room_number')->filter()->unique()->sort()->implode(', ');
                return back()->withInput()->withErrors(['check_in_date' => 'Rooms already booked in this period: ' . ($roomList ?: 'some rooms') . '. Cannot change dates.']);
            }
        } elseif ($whPricingType === 'per_hour' && !in_array($validated['status'] ?? $booking->status, ['cancelled', 'checked_out'])) {
            $bookingDate = $validated['booking_date'] ?? $booking->check_in_date;
            $roomConflicts = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->whereNotNull('room_id')
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<=', $bookingDate)
                ->whereDate('check_out_date', '>=', $bookingDate)
                ->with('room:id,room_number')
                ->get();
            $whConflict = Booking::where('hotel_id', $hotelId)
                ->where('id', '!=', $booking->id)
                ->where('is_whole_hotel', true)
                ->whereNotIn('status', ['cancelled', 'checked_out'])
                ->whereDate('check_in_date', '<=', $bookingDate)
                ->whereDate('check_out_date', '>=', $bookingDate)
                ->first();
            if ($whConflict) {
                return back()->withInput()->withErrors(['booking_date' => 'Blocked by whole-hotel reservation ' . $whConflict->booking_number . '. Dates overlap with an existing whole-hotel booking.']);
            }
            if ($roomConflicts->count() > 0) {
                $roomList = $roomConflicts->pluck('room.room_number')->filter()->unique()->sort()->implode(', ');
                return back()->withInput()->withErrors(['booking_date' => 'Rooms already booked on this date: ' . ($roomList ?: 'some rooms') . '. Cannot change dates to overlap with them.']);
            }
        }

        $advancePayment = (float) ($validated['advance_payment'] ?? $booking->advance_payment ?? 0);
        $customTotal    = (float) $request->input('custom_total', 0);

        if ($whPricingType === 'per_night') {
            $nights      = max(1, Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date'])));
            $baseTotal   = $customTotal > 0 ? $customTotal : (float) $booking->total_amount;
            $updateData  = [
                'customer_id'     => $validated['customer_id'],
                'check_in_date'   => $validated['check_in_date'],
                'check_out_date'  => $validated['check_out_date'],
                'nights'          => $nights,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => $validated['status'],
                'total_amount'    => $baseTotal,
                'advance_payment' => $advancePayment,
                'balance_due'     => max(0, $baseTotal - $advancePayment),
                'payment_status'  => $advancePayment >= $baseTotal ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'price_overridden'=> $customTotal > 0,
            ];
        } elseif ($whPricingType === 'per_slot') {
            $ciDate    = $validated['check_in_date'];
            $coDate    = $validated['check_out_date'];
            $nights    = max(0, Carbon::parse($ciDate)->diffInDays(Carbon::parse($coDate)));
            $baseTotal = $customTotal > 0 ? $customTotal : (float) $booking->total_amount;
            $updateData = [
                'customer_id'     => $validated['customer_id'],
                'check_in_date'   => $ciDate,
                'check_out_date'  => $coDate,
                'booking_date'    => $ciDate,
                'time_slot_id'    => $validated['time_slot_id'] ?: null,
                'nights'          => $nights,
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => $validated['status'],
                'total_amount'    => $baseTotal,
                'advance_payment' => $advancePayment,
                'balance_due'     => max(0, $baseTotal - $advancePayment),
                'payment_status'  => $advancePayment >= $baseTotal ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'price_overridden'=> $customTotal > 0,
            ];
        } else {
            $baseTotal  = $customTotal > 0 ? $customTotal : (float) $booking->total_amount;
            $updateData = [
                'customer_id'     => $validated['customer_id'],
                'adults'          => $validated['adults'],
                'children'        => $validated['children'] ?? 0,
                'special_requests'=> $validated['special_requests'] ?? null,
                'status'          => $validated['status'],
                'total_amount'    => $baseTotal,
                'advance_payment' => $advancePayment,
                'balance_due'     => max(0, $baseTotal - $advancePayment),
                'payment_status'  => $advancePayment >= $baseTotal ? 'paid' : ($advancePayment > 0 ? 'partial' : 'pending'),
                'price_overridden'=> $customTotal > 0,
            ];
        }

        $booking->update($updateData);
        ActivityLogger::log('Updated', 'Booking', 'Updated whole-hotel booking #' . $booking->booking_number);
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Booking updated!');
    }
}
