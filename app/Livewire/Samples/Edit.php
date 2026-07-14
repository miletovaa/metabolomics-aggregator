<?php

namespace App\Livewire\Samples;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Edit extends Component
{
    public Sample $sample;

    public string $labSampleId = '';
    public string $externalId = '';
    public string $matrixGroup = '';
    public string $group = '';
    public string $subgroup = '';
    public string $dateReceived = '';
    public string $storageCondition = '';
    public array $storageConditionDetails = [];
    public ?int $responsibleAnalystId = null;
    public ?int $projectId = null;
    public string $newProjectName = '';
    public string $selectedProjectName = '';
    public array $purposeOfAnalysis = [];
    public array $plannedAnalysis = [];
    public string $sampleType = '';
    public array $typeDetails = [];

    public function mount(Sample $sample): void
    {
        $this->sample = $sample;

        $this->labSampleId = (string) $sample->lab_sample_id;
        $this->externalId = (string) $sample->external_id;
        $this->matrixGroup = (string) $sample->matrix_group;
        $this->group = (string) $sample->sample_group;
        $this->subgroup = (string) $sample->sample_subgroup;
        $this->dateReceived = $sample->date_received?->format('Y-m-d') ?? '';
        $this->storageCondition = (string) $sample->storage_condition;
        $this->storageConditionDetails = $sample->storage_condition_details ?? [];
        $this->responsibleAnalystId = $sample->responsible_analyst_id;
        $this->projectId = $sample->project_id;
        $this->selectedProjectName = $sample->project?->name ?? '';
        $this->purposeOfAnalysis = $sample->purpose_of_analysis ?? [];
        $this->plannedAnalysis = $sample->planned_analysis ?? [];
        $this->sampleType = (string) $sample->sample_type;
        $this->typeDetails = $sample->type_details ?? [];
    }

    public function updatedGroup(): void
    {
        $this->subgroup = '';
    }

    public function updatedSampleType(): void
    {
        $this->typeDetails = [];
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $project = $this->resolveProject();

        $changes = [];
        if ($this->sample->project_id !== $project?->id) {
            $changes['project'] = ($this->sample->project?->name ?? '—') . ' → ' . ($project?->name ?? '—');
        }

        $this->sample->update([
            'lab_sample_id' => $this->labSampleId ?: null,
            'external_id' => $this->externalId ?: null,
            'matrix_group' => $this->matrixGroup ?: null,
            'sample_group' => $this->group,
            'sample_subgroup' => $this->subgroup ?: null,
            'date_received' => $this->dateReceived ?: null,
            'storage_condition' => $this->storageCondition ?: null,
            'storage_condition_details' => $this->storageConditionDetails ?: null,
            'responsible_analyst_id' => $this->responsibleAnalystId,
            'project_id' => $project?->id,
            'purpose_of_analysis' => $this->purposeOfAnalysis ?: null,
            'planned_analysis' => $this->plannedAnalysis ?: null,
            'sample_type' => $this->sampleType ?: null,
            'type_details' => $this->sampleType ? (array_filter($this->typeDetails, fn ($v) => $v !== '' && $v !== null && $v !== []) ?: null) : null,
        ]);

        ActivityLogger::editSample($this->sample, $changes);

        session()->flash('success', 'Sample updated.');

        $this->redirect(route('samples.index'), navigate: true);
    }

    protected function resolveProject(): ?Project
    {
        if (trim($this->newProjectName) !== '') {
            return Project::firstOrCreate(
                ['name' => trim($this->newProjectName)],
                ['status' => 'active', 'user_id' => Auth::id()],
            );
        }

        if ($this->projectId) {
            return Project::find($this->projectId);
        }

        return null;
    }

    protected function rules(): array
    {
        return [
            'group' => ['required', 'in:' . implode(',', array_keys(Sample::GROUPS))],
            'subgroup' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== '' && ! isset(Sample::SUBGROUPS[$this->group][$value])) {
                    $fail('Please select a valid subgroup for the chosen group.');
                }
            }],
            'dateReceived' => ['nullable', 'date'],
            'storageCondition' => ['nullable', 'in:' . implode(',', array_keys(Sample::STORAGE_CONDITIONS))],
            'responsibleAnalystId' => ['nullable', 'exists:users,id'],
            'projectId' => ['nullable', 'exists:projects,id'],
            'sampleType' => ['nullable', 'in:' . implode(',', array_keys(Sample::SAMPLE_TYPES))],
        ];
    }

    public function render()
    {
        return view('livewire.samples.edit', [
            'users' => User::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'subgroupOptions' => Sample::SUBGROUPS[$this->group] ?? [],
        ])->layout('layouts.app');
    }
}
