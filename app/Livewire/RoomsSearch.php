<?php

namespace App\Livewire;

use App\Models\Room;
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
            'maintenance' => Room::where('status', 'maintenance')->count(),
            'inactive'    => Room::where('status', 'inactive')->count(),
        ];

        return view('livewire.rooms-search', compact('rooms', 'stats'));
    }
}
