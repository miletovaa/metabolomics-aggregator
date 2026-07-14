<?php

namespace App\Livewire\Samples;

use App\Models\Sample;
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

    public function deleteSample(int $id): void
    {
        $sample = Sample::findOrFail($id);
        $name = $sample->lab_sample_id ?: ($sample->external_id ?: "#{$sample->id}");
        $sample->delete();
        ActivityLogger::deleteSample($name, $id);
        $this->successMessage = 'Sample deleted.';
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $samples = Sample::query()
            ->with(['project', 'responsibleAnalyst'])
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('lab_sample_id', 'ilike', "%{$search}%")
                        ->orWhere('external_id', 'ilike', "%{$search}%")
                        ->orWhere('matrix_group', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.samples.index', [
            'samples' => $samples,
        ])->layout('layouts.app');
    }
}
