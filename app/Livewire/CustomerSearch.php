<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerSearch extends Component
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
        $query = Customer::withCount('bookings');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.customer-search', compact('customers'));
    }
}
