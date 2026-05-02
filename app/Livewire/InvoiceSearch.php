<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceSearch extends Component
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

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedStatus(): void   { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search   = '';
        $this->status   = '';
        $this->dateFrom = '';
        $this->dateTo   = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Invoice::with(['booking.room', 'customer']);

        if ($this->status)   $query->where('status', $this->status);
        if ($this->dateFrom) $query->whereDate('issued_at', '>=', $this->dateFrom);
        if ($this->dateTo)   $query->whereDate('issued_at', '<=', $this->dateTo);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%$s%")
                  ->orWhere('total_amount', 'like', "%$s%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")
                                                        ->orWhere('company_name', 'like', "%$s%")
                                                        ->orWhere('gstin', 'like', "%$s%"))
                  ->orWhereHas('booking.room', fn($r) => $r->where('room_number', 'like', "%$s%"));
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.invoice-search', compact('invoices'));
    }
}
