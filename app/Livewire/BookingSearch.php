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

    public function render()
    {
        $query = Booking::with(['customer', 'room', 'timeSlot']);

        if ($this->status) {
            $query->where('status', $this->status);
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
