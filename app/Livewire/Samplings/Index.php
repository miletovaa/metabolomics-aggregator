<?php

namespace App\Livewire\Samplings;

use App\Models\Sampling;
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

    public function deleteSampling(int $id): void
    {
        $sampling = Sampling::findOrFail($id);
        $name = $sampling->sample?->lab_sample_id ?: "#{$sampling->id}";
        $sampling->delete();
        ActivityLogger::deleteSampling($name, $id);
        $this->successMessage = 'Sampling deleted.';
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $samplings = Sampling::query()
            ->with('sample')
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('place_of_sampling', 'ilike', "%{$search}%")
                        ->orWhere('country_of_sampling', 'ilike', "%{$search}%")
                        ->orWhereHas('sample', fn ($sq) => $sq->where('lab_sample_id', 'ilike', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.samplings.index', [
            'samplings' => $samplings,
        ])->layout('layouts.app');
    }
}
