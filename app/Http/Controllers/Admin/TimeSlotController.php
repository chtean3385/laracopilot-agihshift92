<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelTimeSlot;
use App\Models\Module;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    private function checkAccess(): bool
    {
        return session('crm_logged_in') && Module::isEnabled('time-slot-pricing');
    }

    private function hasAnySlotModule(): bool
    {
        return Module::isEnabled('time-slot-pricing') || Module::isEnabled('hourly-pricing');
    }

    public function index()
    {
        if (!session('crm_logged_in')) return redirect()->route('login');
        if (!$this->hasAnySlotModule()) {
            return redirect()->route('settings.index')->with('error', 'Time Slot Pricing or Hourly Pricing module is not enabled.');
        }
        $slots              = HotelTimeSlot::ordered()->get();
        $showTimeSlots      = Module::isEnabled('time-slot-pricing');
        $showHourlyPricing  = Module::isEnabled('hourly-pricing');
        return view('admin.settings.time-slots', compact('slots', 'showTimeSlots', 'showHourlyPricing'));
    }

    public function store(Request $request)
    {
        if (!$this->checkAccess()) return response()->json(['error' => 'Forbidden'], 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'start_time'  => 'required|string|regex:/^\d{2}:\d{2}$/',
            'end_time'    => 'required|string|regex:/^\d{2}:\d{2}$/',
            'is_overnight'=> 'nullable|boolean',
            'base_price'  => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $count = HotelTimeSlot::count();
        $slot  = HotelTimeSlot::create(array_merge($validated, [
            'is_overnight' => $request->boolean('is_overnight'),
            'sort_order'   => $count,
            'is_active'    => true,
        ]));

        ActivityLogger::log('Created', 'TimeSlot', 'Created time slot: ' . $slot->name);

        $warnings = $this->checkSlotDefinitionOverlaps($slot);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'slot' => $slot, 'warnings' => $warnings]);
        }
        return redirect()->route('time-slots.index')->with('success', 'Time slot added!');
    }

    public function update(Request $request, HotelTimeSlot $timeSlot)
    {
        if (!$this->checkAccess()) return response()->json(['error' => 'Forbidden'], 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'start_time'  => 'required|string|regex:/^\d{2}:\d{2}$/',
            'end_time'    => 'required|string|regex:/^\d{2}:\d{2}$/',
            'is_overnight'=> 'nullable|boolean',
            'base_price'  => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $timeSlot->update(array_merge($validated, [
            'is_overnight' => $request->boolean('is_overnight'),
        ]));

        ActivityLogger::log('Updated', 'TimeSlot', 'Updated time slot: ' . $timeSlot->name);

        $warnings = $this->checkSlotDefinitionOverlaps($timeSlot);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'slot' => $timeSlot->fresh(), 'warnings' => $warnings]);
        }
        return redirect()->route('time-slots.index')->with('success', 'Time slot updated!');
    }

    public function toggle(HotelTimeSlot $timeSlot)
    {
        $timeSlot->update(['is_active' => !$timeSlot->is_active]);
        $status = $timeSlot->is_active ? 'enabled' : 'disabled';
        ActivityLogger::log('Updated', 'TimeSlot', "Time slot '{$timeSlot->name}' {$status}");
        return response()->json(['success' => true, 'is_active' => $timeSlot->is_active]);
    }

    public function reorder(Request $request)
    {
        if (!$this->checkAccess()) return response()->json(['error' => 'Forbidden'], 403);
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $index => $id) {
            HotelTimeSlot::where('id', $id)->update(['sort_order' => $index]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy(HotelTimeSlot $timeSlot)
    {
        if (!$this->checkAccess()) abort(403);
        $name = $timeSlot->name;
        $timeSlot->delete();
        ActivityLogger::log('Deleted', 'TimeSlot', 'Deleted time slot: ' . $name);
        if (request()->expectsJson()) return response()->json(['success' => true]);
        return redirect()->route('time-slots.index')->with('success', 'Time slot deleted.');
    }

    public function addOnStore(Request $request)
    {
        if (!session('crm_logged_in') || !$this->hasAnySlotModule()) return response()->json(['error' => 'Forbidden'], 403);
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'price'   => 'required|numeric|min:0',
            'room_id' => 'nullable|exists:rooms,id',
        ]);
        $addOn = \App\Models\RoomAddOn::create(array_merge($validated, ['is_active' => true]));
        ActivityLogger::log('Created', 'AddOn', 'Created add-on: ' . $addOn->name);
        return response()->json(['success' => true, 'addOn' => $addOn]);
    }

    public function addOnDestroy($id)
    {
        if (!session('crm_logged_in') || !$this->hasAnySlotModule()) abort(403);
        $addOn = \App\Models\RoomAddOn::findOrFail($id);
        $name  = $addOn->name;
        $addOn->delete();
        ActivityLogger::log('Deleted', 'AddOn', 'Deleted add-on: ' . $name);
        return response()->json(['success' => true]);
    }

    private function checkSlotDefinitionOverlaps(HotelTimeSlot $savedSlot): array
    {
        $others = HotelTimeSlot::where('id', '!=', $savedSlot->id)
            ->where('is_active', true)
            ->get();

        $overlapping = [];
        foreach ($others as $other) {
            if ($this->slotsDefinitionOverlap($savedSlot, $other)) {
                $overlapping[] = $other->name;
            }
        }
        return $overlapping;
    }

    private function slotsDefinitionOverlap(HotelTimeSlot $a, HotelTimeSlot $b): bool
    {
        [$aStart, $aEnd] = $this->slotDefinitionRange($a);
        [$bStart, $bEnd] = $this->slotDefinitionRange($b);
        return $aStart < $bEnd && $bStart < $aEnd;
    }

    private function slotDefinitionRange(HotelTimeSlot $slot): array
    {
        $ref   = '2000-01-01';
        $start = \Carbon\Carbon::parse($ref . ' ' . $slot->start_time);
        $end   = \Carbon\Carbon::parse($ref . ' ' . $slot->end_time);
        if ($slot->is_overnight || $end <= $start) {
            $end->addDay();
        }
        return [$start, $end];
    }
}
