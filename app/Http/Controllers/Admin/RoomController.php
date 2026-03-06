<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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
        ];
        return view('admin.rooms.index', compact('rooms', 'stats'));
    }

    public function create()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        return view('admin.rooms.create');
    }

    public function store(Request $request)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $validated = $request->validate([
            'room_number'    => 'required|string|unique:rooms,room_number',
            'type'           => 'required|in:standard,deluxe,suite,villa,penthouse',
            'capacity'       => 'required|integer|min:1|max:20',
            'price_per_night'=> 'required|numeric|min:0',
            'floor'          => 'nullable|integer',
            'view'           => 'nullable|string|max:100',
            'amenities'      => 'nullable|string',
            'description'    => 'nullable|string',
            'status'         => 'required|in:available,occupied,maintenance',
        ]);
        $room = Room::create($validated);
        ActivityLogger::log('Created', 'Room', 'Created room: ' . $room->room_number . ' (' . ucfirst($room->type) . ')');
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
        $room = Room::findOrFail($id);
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, $id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room      = Room::findOrFail($id);
        $validated = $request->validate([
            'room_number'    => 'required|string|unique:rooms,room_number,' . $id,
            'type'           => 'required|in:standard,deluxe,suite,villa,penthouse',
            'capacity'       => 'required|integer|min:1|max:20',
            'price_per_night'=> 'required|numeric|min:0',
            'floor'          => 'nullable|integer',
            'view'           => 'nullable|string|max:100',
            'amenities'      => 'nullable|string',
            'description'    => 'nullable|string',
            'status'         => 'required|in:available,occupied,maintenance',
        ]);
        $room->update($validated);
        ActivityLogger::log('Updated', 'Room', 'Updated room: ' . $room->room_number);
        return redirect()->route('rooms.index')->with('success', 'Room updated!');
    }

    public function destroy($id)
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        $room = Room::findOrFail($id);
        $number = $room->room_number;
        $room->delete();
        ActivityLogger::log('Deleted', 'Room', 'Deleted room: ' . $number);
        return redirect()->route('rooms.index')->with('success', 'Room removed.');
    }
}
