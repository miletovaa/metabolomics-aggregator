<?php

namespace App\Livewire\ActivityLog;

use App\Models\ActivityLog;
use Livewire\Component;

class Index extends Component
{
    public string $filterEvent   = '';
    public string $filterUser    = '';
    public string $search        = '';
    public int    $perPage       = 25;
    public int    $page          = 1;
    public int    $lastPage      = 1;

    public function updated(string $property): void
    {
        if (in_array($property, ['filterEvent', 'filterUser', 'search', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function clearFilters(): void
    {
        $this->filterEvent = '';
        $this->filterUser  = '';
        $this->search      = '';
        $this->page        = 1;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->lastPage) {
            $this->page++;
        }
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
    }

    public function render()
    {
        $query = ActivityLog::with('user')
            ->when($this->filterEvent !== '', fn($q) => $q->where('event_type', $this->filterEvent))
            ->when($this->filterUser  !== '', fn($q) => $q->where('user_id', $this->filterUser))
            ->when($this->search      !== '', function ($q) {
                $s = strtolower(trim($this->search));
                $q->where(function ($inner) use ($s) {
                    $inner->whereRaw('LOWER(details) LIKE ?', ["%{$s}%"])
                          ->orWhereRaw('LOWER(subject_name) LIKE ?', ["%{$s}%"]);
                });
            })
            ->latest();

        $logs = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        $this->lastPage = max(1, $logs->lastPage());

        if ($this->page > $this->lastPage) {
            $this->page = $this->lastPage;
            $logs = $query->paginate($this->perPage, ['*'], 'page', $this->page);
        }

        $users = \App\Models\User::orderBy('name')->pluck('name', 'id');

        return view('livewire.activity-log.index', compact('logs', 'users'))
            ->layout('layouts.app');
    }
}
