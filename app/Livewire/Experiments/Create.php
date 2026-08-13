<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Models\Project;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public string $description = '';
    public string $status = 'planned';
    public ?int $projectId = null;
    public string $newProjectName = '';
    public string $selectedProjectName = '';
    public string $startedAt = '';
    public string $completedAt = '';

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:' . implode(',', array_keys(Experiment::STATUSES))],
            'startedAt' => ['nullable', 'date'],
            'completedAt' => ['nullable', 'date'],
        ]);

        $project = $this->resolveProject();

        $experiment = Experiment::create([
            'project_id' => $project?->id,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'started_at' => $this->startedAt ?: null,
            'completed_at' => $this->completedAt ?: null,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::createExperiment($experiment);

        $this->redirect(route('experiments.show', $experiment), navigate: true);
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
            return Project::visibleTo(Auth::user())->find($this->projectId);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.experiments.create', [
            'projects' => Project::visibleTo(Auth::user())->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
