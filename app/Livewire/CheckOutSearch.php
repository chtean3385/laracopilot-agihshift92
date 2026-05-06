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

        // Build a per-hotel tax-rate map so the card shows the correct GST-inclusive balance
        // even when a super-admin is viewing bookings that span multiple hotels.
        $hotelIds    = $pendingCheckouts->pluck('hotel_id')->unique()->filter()->values();
        $settingsMap = Setting::whereIn('hotel_id', $hotelIds)->get()->keyBy('hotel_id');
        $taxRateMap  = $hotelIds->mapWithKeys(function ($hid) use ($settingsMap) {
            $s = $settingsMap->get($hid);
            return [$hid => ($s && !empty($s->gst_number) && $s->tax_rate > 0) ? (float) $s->tax_rate : 0];
        })->toArray();

        return view('livewire.check-out-search', compact('pendingCheckouts', 'taxRateMap'));
    }
}
