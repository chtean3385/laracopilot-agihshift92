<?php

namespace App\Livewire;

use App\Models\Payment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $paymentMethod = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedPaymentMethod(): void { $this->resetPage(); }
    public function updatedDateFrom(): void      { $this->resetPage(); }
    public function updatedDateTo(): void        { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search        = '';
        $this->paymentMethod = '';
        $this->dateFrom      = '';
        $this->dateTo        = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Payment::with(['booking.customer', 'booking.room']);

        if ($this->paymentMethod) $query->where('payment_method', $this->paymentMethod);
        if ($this->dateFrom)      $query->whereDate('created_at', '>=', $this->dateFrom);
        if ($this->dateTo)        $query->whereDate('created_at', '<=', $this->dateTo);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_id', 'like', "%$s%")
                  ->orWhere('amount', 'like', "%$s%")
                  ->orWhereHas('booking', fn($b) => $b->where('booking_number', 'like', "%$s%"))
                  ->orWhereHas('booking.customer', fn($c) => $c->where('name', 'like', "%$s%"));
            });
        }

        $payments     = $query->orderBy('created_at', 'desc')->paginate(20);
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');

        return view('livewire.payment-search', compact('payments', 'totalRevenue'));
    }
}
