<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Services\ActivityLogger;
use Livewire\Component;

class Show extends Component
{
    public Experiment $experiment;
    public ?string $successMessage = null;

    public function mount(Experiment $experiment): void
    {
        $this->experiment = $experiment;
        $this->successMessage = session('success');
    }

    public function deleteRecord(int $id): void
    {
        $record = ExperimentRecord::findOrFail($id);
        abort_unless($record->experiment_id === $this->experiment->id, 404);

        $label = $record->recordTypeLabel();
        $record->delete();
        ActivityLogger::deleteExperimentRecord($label, $this->experiment);
        $this->successMessage = 'Record deleted.';
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $records = $this->experiment->records()
            ->with(['sample', 'performedBy', 'parentRecord'])
            ->orderBy('record_type')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (ExperimentRecord $record) => ExperimentRecord::familyOf($record->record_type));

        return view('livewire.experiments.show', [
            'recordsByFamily' => $records,
            'samples' => $this->experiment->samples(),
        ])->layout('layouts.app');
    }
}
