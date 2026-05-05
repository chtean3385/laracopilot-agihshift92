<?php

namespace App\Livewire;

use App\Models\Room;
use App\Services\ActivityLogger;
use App\Services\HotelContext;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RoomsSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedType(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->type   = '';
        $this->resetPage();
    }

    // Force-reset a stuck occupied room back to available without going through checkout.
    // Used when the booking was deleted externally or the checkout was never recorded.
    public function forceAvailable(int $roomId): void
    {
        $hotelId = app(HotelContext::class)->getHotel();
        $room    = Room::where('id', $roomId)->where('hotel_id', $hotelId)->first();

        if (!$room || $room->status !== 'occupied') {
            return;
        }

        DB::table('rooms')->where('id', $roomId)->update(['status' => 'available']);
        ActivityLogger::log('room_force_available', 'Room', "Room {$room->room_number} manually reset to Available by admin.");

        session()->flash('success', "Room {$room->room_number} has been reset to Available.");
    }

    // Mark a dirty room as available after housekeeping is done.
    public function markAvailable(int $roomId): void
    {
        $hotelId = app(HotelContext::class)->getHotel();
        $room    = Room::where('id', $roomId)->where('hotel_id', $hotelId)->first();

        if (!$room || $room->status !== 'dirty') {
            return;
        }

        DB::table('rooms')->where('id', $roomId)->update(['status' => 'available']);
        ActivityLogger::log('room_marked_available', 'Room', "Room {$room->room_number} marked Available after housekeeping by admin.");

        session()->flash('success', "Room {$room->room_number} is now available for new bookings.");
    }

    public function render()
    {
        $query = Room::query();

        if ($this->status) $query->where('status', $this->status);
        if ($this->type)   $query->where('type', $this->type);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('room_number', 'like', "%$s%")
                  ->orWhere('view', 'like', "%$s%")
                  ->orWhere('amenities', 'like', "%$s%")
                  ->orWhere('type', 'like', "%$s%");
            });
        }

        $rooms = $query->with([
            'timeSlots',
            'bookings' => fn ($q) => $q->where('status', 'checked_in')->latest()->limit(1),
        ])->orderBy('room_number')->paginate(20);

        $stats = [
            'available'   => Room::where('status', 'available')->count(),
            'occupied'    => Room::where('status', 'occupied')->count(),
            'dirty'       => Room::where('status', 'dirty')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
            'inactive'    => Room::where('status', 'inactive')->count(),
        ];

        return view('livewire.rooms-search', compact('rooms', 'stats'));
    }
}
