<?php

namespace App\Livewire\ExperimentRecords;

use App\Models\Compound;
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

    public string $compoundSearch = '';

    public function mount(Experiment $experiment, ExperimentRecord $record): void
    {
        abort_unless($record->experiment_id === $experiment->id, 404);

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

    public function selectCompound(string $field, int $compoundId, string $label): void
    {
        $this->detailsData[$field] = $compoundId;
        $this->compoundSearch = '';
    }

    public function createCompound(string $field): void
    {
        $name = trim($this->compoundSearch);
        if ($name === '') {
            return;
        }

        $compound = Compound::firstOrCreate(['canonical_name' => $name]);
        $this->detailsData[$field] = $compound->id;
        $this->compoundSearch = '';
    }

    public function getCompoundSearchResultsProperty()
    {
        if (trim($this->compoundSearch) === '') {
            return collect();
        }

        return Compound::where('canonical_name', 'ilike', '%' . trim($this->compoundSearch) . '%')
            ->orderBy('canonical_name')
            ->limit(15)
            ->get(['id', 'canonical_name']);
    }

    public function compoundLabel(?int $id): ?string
    {
        return $id ? Compound::find($id)?->canonical_name : null;
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

        $this->record->update([
            'sample_id' => $this->sampleId,
            'parent_record_id' => $this->parentRecordId,
            'performed_by' => $this->performedBy,
            'performed_at' => $this->performedAt ?: null,
            'note' => $this->note ?: null,
            'details' => $details ?: null,
        ]);

        session()->flash('success', 'Record updated.');

        $this->redirect(route('experiments.show', $this->experiment), navigate: true);
    }

    public function render()
    {
        return view('livewire.experiment-records.edit', [
            'samples' => Sample::orderByDesc('id')->get(['id', 'lab_sample_id', 'external_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'fattyAcids' => Compound::whereNotNull('lipid_class')->orderBy('canonical_name')->get(['id', 'canonical_name']),
            'parentCandidates' => $this->experiment->records()
                ->where('id', '!=', $this->record->id)
                ->when($this->sampleId, fn ($q) => $q->where('sample_id', $this->sampleId))
                ->get(),
            'fieldSchema' => ExperimentRecord::fieldSchema($this->recordType),
        ])->layout('layouts.app');
    }
}
