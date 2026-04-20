<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CheckOutSearch extends Component
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
        $query = Booking::with(['customer', 'room', 'payments', 'extraCharges'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
                  ->orWhereHas('room', fn($r) => $r->where('room_number', 'like', "%$s%")->orWhere('type', 'like', "%$s%"));
            });
        }

        $pendingCheckouts = $query->paginate(12);

        $hotelId  = session('crm_hotel_id');
        $settings = Setting::where('hotel_id', $hotelId)->first();
        $taxRate  = ($settings && !empty($settings->gst_number) && $settings->tax_rate > 0)
                    ? (float) $settings->tax_rate : 0;

        return view('livewire.check-out-search', compact('pendingCheckouts', 'taxRate'));
    }
}
