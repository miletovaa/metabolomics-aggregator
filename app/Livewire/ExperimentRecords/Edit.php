<?php

namespace App\Livewire\ExperimentRecords;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Sample;
use App\Models\User;
use App\Services\ActivityLogger;
use Livewire\Component;

class Edit extends Component
{
    public Experiment $experiment;
    public ExperimentRecord $record;

    public ?int $sampleId = null;
    public string $recordType = '';
    public ?int $parentRecordId = null;
    public ?int $performedBy = null;
    public string $performedAt = '';
    public string $note = '';
    public array $detailsData = [];

    public function mount(Experiment $experiment, ExperimentRecord $record): void
    {
        abort_unless($record->experiment_id === $experiment->id, 404);
        // Compound-based results now live as ProjectCompound rows, managed on the scoped
        // project-compounds page — there should be no ExperimentRecord rows of these types
        // left to edit, but guard against a stale link just in case.
        abort_if(in_array($record->record_type, ExperimentRecord::COMPOUND_RESULT_TYPES, true), 404);

        $this->experiment = $experiment;
        $this->record = $record;

        $this->sampleId = $record->sample_id;
        $this->recordType = $record->record_type;
        $this->parentRecordId = $record->parent_record_id;
        $this->performedBy = $record->performed_by;
        $this->performedAt = $record->performed_at?->format('Y-m-d') ?? '';
        $this->note = (string) $record->note;
        $this->detailsData = $record->details ?? [];
    }

    public function save(): void
    {
        $this->validate([
            'sampleId' => ['required', 'exists:samples,id'],
            'parentRecordId' => ['nullable', 'exists:experiment_records,id'],
            'performedAt' => ['nullable', 'date'],
        ]);

        $details = [];
        foreach (ExperimentRecord::fieldSchema($this->recordType) as $field) {
            $value = $this->detailsData[$field['key']] ?? null;
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            $details[$field['key']] = $value;
        }

        $this->record->fill([
            'sample_id' => $this->sampleId,
            'parent_record_id' => $this->parentRecordId,
            'performed_by' => $this->performedBy,
            'performed_at' => $this->performedAt ?: null,
            'note' => $this->note ?: null,
            'details' => $details ?: null,
        ]);

        $changes = ActivityLogger::diff($this->record);
        $this->record->save();

        ActivityLogger::editExperimentRecord($this->record, $changes);

        session()->flash('success', 'Record updated.');

        $this->redirect(route('experiments.show', $this->experiment), navigate: true);
    }

    public function render()
    {
        return view('livewire.experiment-records.edit', [
            'samples' => Sample::orderByDesc('id')->get(['id', 'lab_sample_id', 'external_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'parentCandidates' => $this->experiment->records()
                ->where('id', '!=', $this->record->id)
                ->when($this->sampleId, fn ($q) => $q->where('sample_id', $this->sampleId))
                ->get(),
            'fieldSchema' => ExperimentRecord::fieldSchema($this->recordType),
        ])->layout('layouts.app');
    }
}
