<?php

namespace App\Livewire;

use App\Models\Booking;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Booking::with(['customer', 'room'])
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', '<=', Carbon::today())
            ->orderBy('check_in_date');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%$s%")->orWhere('type', 'like', "%$s%"));
            });
        }

        $pendingCheckins = $query->paginate(12);

        return view('livewire.check-in-search', compact('pendingCheckins'));
    }
}
