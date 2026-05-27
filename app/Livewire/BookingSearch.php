<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Room;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BookingSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $roomType = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedRoomType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search    = '';
        $this->status    = '';
        $this->dateFrom  = '';
        $this->dateTo    = '';
        $this->roomType  = '';
        $this->resetPage();
    }

    public function deleteBooking(int $id): void
    {
        if (!\App\Services\PermissionService::check('bookings.delete')) {
            return;
        }

        $booking = Booking::with('groupedBookings.room')->findOrFail($id);
        $number  = $booking->booking_number;

        // Cancel all child (grouped) bookings too and free their rooms
        foreach ($booking->groupedBookings as $child) {
            $child->update(['status' => 'cancelled']);
            if ($child->room) {
                $child->room->update(['status' => 'available']);
            }
        }

        $booking->update(['status' => 'cancelled']);

        if ($booking->room) {
            $booking->room->update(['status' => 'available']);
        }

        \App\Services\ActivityLogger::log('Deleted', 'Booking', 'Cancelled booking #' . $number);
        $this->dispatch('crm-toast', type: 'success', message: 'Booking #' . $number . ' has been deleted.');
    }

    public function render()
    {
        // Only show primary bookings — child bookings (part of a group) are hidden
        // from the list and accessible via the primary booking's show page.
        $query = Booking::with(['customer', 'room', 'timeSlot', 'groupedBookings.room'])
            ->whereNull('group_booking_id');

        if ($this->status) {
            $query->where('status', $this->status);
        } else {
            // Cancelled bookings are hidden by default; filter by status=cancelled to see them
            $query->where('status', '!=', 'cancelled');
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%$search%"))
                  ->orWhere('booking_number', 'like', "%$search%");
            });
        }

        if ($this->dateFrom) {
            $df = $this->dateFrom;
            $query->where(function ($q) use ($df) {
                $q->whereDate('check_in_date', '>=', $df)
                  ->orWhereDate('booking_date', '>=', $df);
            });
        }

        if ($this->dateTo) {
            $dt = $this->dateTo;
            $query->where(function ($q) use ($dt) {
                $q->whereDate('check_out_date', '<=', $dt)
                  ->orWhereDate('booking_date', '<=', $dt);
            });
        }

        if ($this->roomType) {
            $query->whereHas('room', fn($r) => $r->where('type', $this->roomType));
        }

        $bookings   = $query->orderBy('created_at', 'desc')->paginate(15);
        $roomTypes  = Room::distinct()->orderBy('type')->pluck('type');

        return view('livewire.booking-search', compact('bookings', 'roomTypes'));
    }
}
