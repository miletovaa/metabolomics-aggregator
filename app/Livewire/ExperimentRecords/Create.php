<?php

namespace App\Livewire\ExperimentRecords;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Sample;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class Create extends Component
{
    public Experiment $experiment;

    // Preselectable via query string — the "Add results" shortcut on an analysis record
    // links here with these filled in, so the corresponding result type, its sample, and
    // the analysis record as parent are ready to go without re-picking them.
    #[Url]
    public ?int $sampleId = null;
    public string $selectedSampleLabel = '';
    #[Url]
    public string $recordType = '';
    #[Url]
    public ?int $parentRecordId = null;
    public ?int $performedBy = null;
    public string $performedAt = '';
    public string $note = '';
    public array $detailsData = [];

    public function mount(Experiment $experiment): void
    {
        abort_unless(Experiment::visibleTo(Auth::user(), 'edit')->whereKey($experiment->id)->exists(), 404);

        $this->experiment = $experiment;
    }

    public function updatedRecordType(): void
    {
        $this->detailsData = [];
    }

    public function save(): void
    {
        $this->validate([
            'sampleId' => ['required', 'exists:samples,id'],
            'recordType' => ['required', 'in:' . implode(',', array_keys(ExperimentRecord::RECORD_TYPES))],
            'parentRecordId' => ['nullable', 'exists:experiment_records,id'],
            'performedAt' => ['nullable', 'date'],
        ]);

        // GC-MS/VOC-GC-MS/IRMS results are managed as ProjectCompound rows on the scoped
        // project-compounds page (mapping/mz/rt/import-export), not as ExperimentRecord rows —
        // see ExperimentRecord::COMPOUND_RESULT_TYPES.
        if (in_array($this->recordType, ExperimentRecord::COMPOUND_RESULT_TYPES, true)) {
            if (!$this->experiment->project_id) {
                $this->addError(
                    'recordType',
                    'This experiment has no project assigned. Assign a project to the experiment before adding '
                        . ExperimentRecord::RECORD_TYPES[$this->recordType] . ' results.',
                );
                return;
            }

            $this->redirect(route('experiments.results', [
                'experiment' => $this->experiment->id,
                'sampleId' => $this->sampleId,
                'recordType' => $this->recordType,
                'performedBy' => $this->performedBy,
                'performedAt' => $this->performedAt ?: null,
                'parentRecordId' => $this->parentRecordId,
            ]), navigate: true);

            return;
        }

        $details = [];
        foreach (ExperimentRecord::fieldSchema($this->recordType) as $field) {
            $value = $this->detailsData[$field['key']] ?? null;
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            $details[$field['key']] = $value;
        }

        $record = ExperimentRecord::create([
            'experiment_id' => $this->experiment->id,
            'sample_id' => $this->sampleId,
            'parent_record_id' => $this->parentRecordId,
            'record_type' => $this->recordType,
            'performed_by' => $this->performedBy,
            'performed_at' => $this->performedAt ?: null,
            'note' => $this->note ?: null,
            'details' => $details ?: null,
        ]);

        ActivityLogger::createExperimentRecord($record);

        session()->flash('success', 'Record added.');

        $this->redirect(route('experiments.show', $this->experiment), navigate: true);
    }

    public function render()
    {
        return view('livewire.experiment-records.create', [
            'samples' => Sample::visibleTo(Auth::user())->orderByDesc('id')->get(['id', 'lab_sample_id', 'external_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'parentCandidates' => $this->experiment->records()
                ->when($this->sampleId, fn ($q) => $q->where('sample_id', $this->sampleId))
                ->get(),
            'fieldSchema' => $this->recordType ? ExperimentRecord::fieldSchema($this->recordType) : [],
        ])->layout('layouts.app');
    }
}
