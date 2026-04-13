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

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $customers = Customer::orderBy('name')->get();
        $rooms     = Room::where('status', 'available')->orderBy('room_number')->get();

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

        return view('admin.bookings.create', compact('customers', 'rooms', 'slotModuleOn', 'hourlyModuleOn', 'hotelModules', 'timeSlots', 'addOns'));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $room         = Room::findOrFail($request->input('room_id'));
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
            'room_id'         => 'required|exists:rooms,id',
            'adults'          => 'required|integer|min:1',
            'children'        => 'nullable|integer|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'special_requests'=> 'nullable|string',
        ];
        if ($pricingType === 'per_slot') {
            $rules = array_merge($baseRules, [
                'booking_date'   => 'required|date',
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
                'check_out_date' => 'required|date|after:check_in_date',
            ]);
        }
        $validated = $request->validate($rules);

        // ── Per-slot overlap guard ──────────────────────────────────────────────
        if ($pricingType === 'per_slot') {
            $targetSlot = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
            $conflicting = (new \App\Services\SlotConflictService())->getConflictingRoomIds(
                $targetSlot,
                $validated['booking_date']
            );
            if (in_array((int) $validated['room_id'], $conflicting)) {
                return back()
                    ->withInput()
                    ->withErrors(['time_slot_id' => 'This room is already booked for an overlapping time slot on this date.']);
            }
        }

        $bookingPrefix  = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', session('crm_hotel_name', 'HOT')), 0, 3));
        $bookingNumber  = $bookingPrefix . '-BK-' . strtoupper(substr(uniqid(), -6));

        if ($pricingType === 'per_slot') {
            $slot          = \App\Models\HotelTimeSlot::findOrFail($validated['time_slot_id']);
            $totalAmount   = $slot->base_price;
            $advancePayment= $validated['advance_payment'] ?? 0;
            $bookingData   = [
                'booking_number'  => $bookingNumber,
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $validated['room_id'],
                'check_in_date'   => $validated['booking_date'],
                'check_out_date'  => $validated['booking_date'],
                'booking_date'    => $validated['booking_date'],
                'time_slot_id'    => $validated['time_slot_id'],
                'nights'          => 0,
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
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $validated['room_id'],
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
            // per_night — original flow
            $nights        = Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date']));
            $mealBreakfast = $request->boolean('meal_breakfast') && $room->has_breakfast;
            $mealLunch     = $request->boolean('meal_lunch')     && $room->has_lunch;
            $mealDinner    = $request->boolean('meal_dinner')    && $room->has_dinner;
            $mealCost      = ($mealBreakfast ? ($room->breakfast_price * $nights) : 0)
                           + ($mealLunch     ? ($room->lunch_price     * $nights) : 0)
                           + ($mealDinner    ? ($room->dinner_price    * $nights) : 0);
            $extraBeds     = $room->has_extra_bed ? max(0, (int) $request->input('extra_beds', 0)) : 0;
            $extraBedCost  = $room->has_extra_bed ? ($extraBeds * ($room->extra_bed_price ?? 0) * $nights) : 0;
            $totalAmount   = $nights * $room->price_per_night + $mealCost + $extraBedCost;
            $advancePayment= $validated['advance_payment'] ?? 0;
            $bookingData   = [
                'booking_number'  => $bookingNumber,
                'customer_id'     => $validated['customer_id'],
                'room_id'         => $validated['room_id'],
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

        $customer = Customer::find($bookingData['customer_id']);
        ActivityLogger::log('Created', 'Booking', 'Booking #' . $booking->booking_number . ' created for ' . ($customer->name ?? 'guest') . ' — Room ' . $room->room_number . ' (' . $pricingType . ')');
        WhatsAppService::sendForEvent('booking.created', $booking);
        return redirect()->route('bookings.show', $booking->id)->with('success', 'Booking created! #' . $booking->booking_number);
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking = Booking::with(['customer', 'room', 'payments', 'invoice', 'bookingGuests', 'timeSlot', 'bookingAddOns', 'extraCharges', 'paymentReferences'])->findOrFail($id);
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
        $bookingHotelId = $booking->room?->hotel_id;
        $slotModuleOn   = $bookingHotelId ? \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $bookingHotelId)->where('slug', 'time-slot-pricing')->where('is_enabled', true)->exists() : false;
        $hourlyModuleOn = $bookingHotelId ? \App\Models\Module::withoutGlobalScopes()
            ->where('hotel_id', $bookingHotelId)->where('slug', 'hourly-pricing')->where('is_enabled', true)->exists() : false;

        // Only show rooms of the same pricing_type to keep pricing context stable.
        $pricingType = $booking->room?->pricing_type ?? 'per_night';
        $rooms = Room::where('pricing_type', $pricingType)->orderBy('room_number')->get();

        $timeSlots = $slotModuleOn ? \App\Models\HotelTimeSlot::where('is_active', true)->ordered()->get() : collect();
        return view('admin.bookings.edit', compact('booking', 'customers', 'rooms', 'slotModuleOn', 'hourlyModuleOn', 'timeSlots'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $booking     = Booking::findOrFail($id);
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
                'check_out_date' => 'required|date|after:check_in_date',
            ]);
        }

        $validated = $request->validate($rules);
        $advancePayment = (float) ($validated['advance_payment'] ?? $booking->advance_payment ?? 0);

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
            // per_night — original flow
            $nights        = Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date']));
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

        $booking->update($updateData);
        $newStatus = $validated['status'];

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
        $booking = Booking::with('room')->findOrFail($id);
        $number  = $booking->booking_number;
        $booking->update(['status' => 'cancelled']);
        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }
        ActivityLogger::log('Deleted', 'Booking', 'Cancelled booking #' . $number);
        return redirect()->route('bookings.index')->with('success', 'Booking cancelled.');
    }
}
