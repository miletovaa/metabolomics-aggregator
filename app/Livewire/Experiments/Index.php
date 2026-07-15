<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $successMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteExperiment(int $id): void
    {
        $experiment = Experiment::findOrFail($id);
        $name = $experiment->name;
        $experiment->delete();
        ActivityLogger::deleteExperiment($name, $id);
        $this->successMessage = 'Experiment deleted.';
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $experiments = Experiment::query()
            ->withCount('records')
            ->with('project')
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        return view('livewire.experiments.index', [
            'experiments' => $experiments,
        ])->layout('layouts.app');
    }
}
