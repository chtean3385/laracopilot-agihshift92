<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Module;
use App\Models\HotelTimeSlot;
use App\Services\ActivityLogger;
use App\Services\HotelContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');

        $query = Room::query();
        if ($request->status) $query->where('status', $request->status);
        if ($request->type)   $query->where('type', $request->type);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('room_number', 'like', "%$s%")
                  ->orWhere('view', 'like', "%$s%")
                  ->orWhere('amenities', 'like', "%$s%")
                  ->orWhere('type', 'like', "%$s%");
            });
        }
        $rooms = $query->orderBy('room_number')->paginate(20)->withQueryString();
        $stats = [
            'available'   => Room::where('status', 'available')->count(),
            'occupied'    => Room::where('status', 'occupied')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
            'inactive'    => Room::where('status', 'inactive')->count(),
        ];
        return view('admin.rooms.index', compact('rooms', 'stats'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $slotModuleOn   = Module::isEnabled('time-slot-pricing');
        $hourlyModuleOn = Module::isEnabled('hourly-pricing');
        $addOns         = ($slotModuleOn || $hourlyModuleOn) ? \App\Models\RoomAddOn::active()->whereNull('room_id')->orderBy('name')->get() : collect();
        return view('admin.rooms.create', compact('slotModuleOn', 'hourlyModuleOn', 'addOns'));
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $slotModuleOn   = Module::isEnabled('time-slot-pricing');
        $hourlyModuleOn = Module::isEnabled('hourly-pricing');
        $allowedTypes   = array_filter(['per_night', $slotModuleOn ? 'per_slot' : null, $hourlyModuleOn ? 'per_hour' : null]);
        $reqType        = $request->input('pricing_type', 'per_night');
        $pricingType    = in_array($reqType, $allowedTypes) ? $reqType : 'per_night';
        $priceRequired = $pricingType === 'per_night' ? 'required|numeric|min:0' : 'nullable|numeric|min:0';
        $validated = $request->validate([
            'room_number'    => ['required', 'string', Rule::unique('rooms', 'room_number')->where('hotel_id', app(HotelContext::class)->getHotel())],
            'type'           => 'required|in:standard,deluxe,non-ac,suite,villa,penthouse,cottage,bhk',
            'capacity'       => 'required|integer|min:1|max:50',
            'price_per_night'=> $priceRequired,
            'hourly_rate'    => 'nullable|numeric|min:0',
            'floor'          => 'nullable|integer',
            'view'           => 'nullable|string|max:100',
            'amenities'      => 'nullable|string',
            'description'    => 'nullable|string',
            'status'         => 'required|in:available,occupied,maintenance,dirty',
            'breakfast_price'=> 'nullable|numeric|min:0',
            'lunch_price'    => 'nullable|numeric|min:0',
            'dinner_price'   => 'nullable|numeric|min:0',
            'extra_bed_price'=> 'nullable|numeric|min:0',
        ]);
        $validated['pricing_type']   = $pricingType;
        $validated['hourly_rate']    = ($pricingType === 'per_hour') ? ($request->input('hourly_rate') ?? 0) : null;
        $validated['price_per_night']= ($pricingType === 'per_night') ? ($validated['price_per_night'] ?? 0) : 0;
        $validated['has_breakfast']  = $request->boolean('has_breakfast');
        $validated['has_lunch']      = $request->boolean('has_lunch');
        $validated['has_dinner']     = $request->boolean('has_dinner');
        $validated['has_extra_bed']  = $request->boolean('has_extra_bed');
        if (!$validated['has_breakfast']) $validated['breakfast_price'] = null;
        if (!$validated['has_lunch'])     $validated['lunch_price']     = null;
        if (!$validated['has_dinner'])    $validated['dinner_price']    = null;
        if (!$validated['has_extra_bed']) $validated['extra_bed_price'] = null;
        $room = Room::create($validated);
        ActivityLogger::log('Created', 'Room', 'Created room: ' . $room->room_number . ' (' . ucfirst($room->type) . ', ' . $pricingType . ')');
        return redirect()->route('rooms.index')->with('success', 'Room added!');
    }

    public function show($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room = Room::with(['bookings.customer'])->findOrFail($id);
        return view('admin.rooms.show', compact('room'));
    }

    public function edit($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room           = Room::findOrFail($id);
        $slotModuleOn   = Module::isEnabled('time-slot-pricing');
        $hourlyModuleOn = Module::isEnabled('hourly-pricing');
        $addOns         = ($slotModuleOn || $hourlyModuleOn) ? \App\Models\RoomAddOn::active()->whereNull('room_id')->orderBy('name')->get() : collect();
        return view('admin.rooms.edit', compact('room', 'slotModuleOn', 'hourlyModuleOn', 'addOns'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room           = Room::findOrFail($id);
        $slotModuleOn   = Module::isEnabled('time-slot-pricing');
        $hourlyModuleOn = Module::isEnabled('hourly-pricing');
        $allowedTypes   = array_filter(['per_night', $slotModuleOn ? 'per_slot' : null, $hourlyModuleOn ? 'per_hour' : null]);
        $reqType        = $request->input('pricing_type', $room->pricing_type ?? 'per_night');
        // If the room's current pricing_type module is disabled, preserve it so the
        // room isn't silently coerced to per_night just because the module is off.
        if (!in_array($room->pricing_type, $allowedTypes)) {
            $pricingType = $room->pricing_type ?? 'per_night';
        } else {
            $pricingType = in_array($reqType, $allowedTypes) ? $reqType : ($room->pricing_type ?? 'per_night');
        }
        $priceRequired = $pricingType === 'per_night' ? 'required|numeric|min:0' : 'nullable|numeric|min:0';
        $validated = $request->validate([
            'room_number'    => ['required', 'string', Rule::unique('rooms', 'room_number')->where('hotel_id', app(HotelContext::class)->getHotel())->ignore($id)],
            'type'           => 'required|in:standard,deluxe,non-ac,suite,villa,penthouse,cottage,bhk',
            'capacity'       => 'required|integer|min:1|max:50',
            'price_per_night'=> $priceRequired,
            'hourly_rate'    => 'nullable|numeric|min:0',
            'floor'          => 'nullable|integer',
            'view'           => 'nullable|string|max:100',
            'amenities'      => 'nullable|string',
            'description'    => 'nullable|string',
            'status'         => 'required|in:available,occupied,maintenance,inactive,dirty',
            'breakfast_price'=> 'nullable|numeric|min:0',
            'lunch_price'    => 'nullable|numeric|min:0',
            'dinner_price'   => 'nullable|numeric|min:0',
            'extra_bed_price'=> 'nullable|numeric|min:0',
        ]);
        $validated['pricing_type']   = $pricingType;
        $validated['hourly_rate']    = ($pricingType === 'per_hour') ? ($request->input('hourly_rate') ?? 0) : null;
        $validated['price_per_night']= ($pricingType === 'per_night') ? ($validated['price_per_night'] ?? 0) : 0;
        $validated['has_breakfast']  = $request->boolean('has_breakfast');
        $validated['has_lunch']      = $request->boolean('has_lunch');
        $validated['has_dinner']     = $request->boolean('has_dinner');
        $validated['has_extra_bed']  = $request->boolean('has_extra_bed');
        if (!$validated['has_breakfast']) $validated['breakfast_price'] = null;
        if (!$validated['has_lunch'])     $validated['lunch_price']     = null;
        if (!$validated['has_dinner'])    $validated['dinner_price']    = null;
        if (!$validated['has_extra_bed']) $validated['extra_bed_price'] = null;
        $room->update($validated);
        ActivityLogger::log('Updated', 'Room', 'Updated room: ' . $room->room_number . ' (' . $pricingType . ')');
        return redirect()->route('rooms.index')->with('success', 'Room updated!');
    }

    public function deactivate($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room = Room::findOrFail($id);

        if ($this->isOccupiedByGuest($room)) {
            return redirect()->route('rooms.index')
                ->with('error', 'Room ' . $room->room_number . ' cannot be deactivated — it is currently occupied or has an active booking today.');
        }

        $room->update(['status' => 'inactive']);
        ActivityLogger::log('Deactivated', 'Room', 'Deactivated room: ' . $room->room_number);
        return redirect()->route('rooms.index')->with('success', 'Room ' . $room->room_number . ' has been deactivated.');
    }

    public function activate($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room = Room::findOrFail($id);
        $room->update(['status' => 'available']);
        ActivityLogger::log('Activated', 'Room', 'Re-activated room: ' . $room->room_number);
        return redirect()->route('rooms.index')->with('success', 'Room ' . $room->room_number . ' is now active and available.');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room = Room::findOrFail($id);

        if ($this->isOccupiedByGuest($room)) {
            return redirect()->route('rooms.index')
                ->with('error', 'Room ' . $room->room_number . ' cannot be deleted — it is currently occupied or has an active booking today.');
        }

        $number = $room->room_number;
        ActivityLogger::log('Deleted', 'Room', 'Deleted room: ' . $number);
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Room ' . $number . ' has been permanently deleted.');
    }

    private function isOccupiedByGuest(Room $room): bool
    {
        $today = now()->toDateString();
        return $room->bookings()
            ->whereIn('status', ['checked_in', 'confirmed', 'pending'])
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today)
            ->exists();
    }
}
