<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\ProjectCompound;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Experiment $experiment;
    public ?string $successMessage = null;

    public function mount(Experiment $experiment): void
    {
        abort_unless(Experiment::visibleTo(Auth::user())->whereKey($experiment->id)->exists(), 404);

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
        $recordGroupsByFamily = $this->experiment->records()
            ->whereNotIn('record_type', ExperimentRecord::COMPOUND_RESULT_TYPES)
            ->with(['sample', 'performedBy', 'parentRecord'])
            ->orderBy('record_type')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (ExperimentRecord $record) => ExperimentRecord::familyOf($record->record_type))
            ->map(fn ($familyRecords) => $familyRecords
                ->groupBy(fn (ExperimentRecord $record) => $record->groupKey())
                ->values()
            );

        $compoundResultGroups = ProjectCompound::where('experiment_id', $this->experiment->id)
            ->whereIn('record_type', ExperimentRecord::COMPOUND_RESULT_TYPES)
            ->with(['sample', 'performedBy', 'parentRecord'])
            ->get()
            ->groupBy(fn (ProjectCompound $pc) => $pc->runGroupKey())
            ->map(function ($rows) {
                $first = $rows->first();

                return (object) [
                    'record_type' => $first->record_type,
                    'sample' => $first->sample,
                    'performedBy' => $first->performedBy,
                    'performed_at' => $first->performed_at,
                    'parentRecord' => $first->parentRecord,
                    'compound_count' => $rows->count(),
                    'sample_id' => $first->sample_id,
                    'performed_by' => $first->performed_by,
                ];
            })
            ->values();

        return view('livewire.experiments.show', [
            'recordGroupsByFamily' => $recordGroupsByFamily,
            'compoundResultGroups' => $compoundResultGroups,
            'samples' => $this->experiment->samples(),
        ])->layout('layouts.app');
    }
}
