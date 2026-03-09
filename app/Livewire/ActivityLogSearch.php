<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogSearch extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $module = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $date = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedModule(): void { $this->resetPage(); }
    public function updatedAction(): void { $this->resetPage(); }
    public function updatedDate(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->module = '';
        $this->action = '';
        $this->date   = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = ActivityLog::query()->orderByDesc('created_at');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('user_name', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('user_email', 'like', "%$s%");
            });
        }

        if ($this->module) $query->where('module', $this->module);
        if ($this->action) $query->where('action', $this->action);
        if ($this->date)   $query->whereDate('created_at', $this->date);

        $logs    = $query->paginate(50);
        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('livewire.activity-log-search', compact('logs', 'modules', 'actions'));
    }
}
