<?php

namespace App\Livewire\Projects;

use App\Models\OptionList;
use App\Models\Project;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public ?string $successMessage = null;
    public ?string $errorMessage   = null;

    public function saveName(int $id, string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            $this->errorMessage = 'Project name cannot be empty.';
            return;
        }

        $project = Project::visibleTo(Auth::user())->findOrFail($id);

        try {
            $oldName = $project->name;
            $project->update(['name' => $name]);
            ActivityLogger::editProject($project, ['name' => "{$oldName} → {$name}"]);
            $this->successMessage = 'Project renamed.';
            $this->errorMessage   = null;
        } catch (\Exception $e) {
            $this->errorMessage   = 'Could not rename project: a project with that name may already exist.';
            $this->successMessage = null;
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! isset(OptionList::optionsFor('project_statuses')[$status])) {
            return;
        }

        $project = Project::visibleTo(Auth::user())->findOrFail($id);
        $oldStatus = $project->status;
        $project->update(['status' => $status]);
        ActivityLogger::editProject($project, ['status' => "{$oldStatus} → {$status}"]);

        $this->successMessage = 'Status updated.';
        $this->errorMessage   = null;
    }

    public function deleteProject(int $id): void
    {
        $project = Project::visibleTo(Auth::user())->findOrFail($id);
        $name    = $project->name;
        $project->delete();
        ActivityLogger::deleteProject($name, $id);

        $this->successMessage = 'Project deleted.';
        $this->errorMessage   = null;
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
        $this->errorMessage   = null;
    }

    public function render()
    {
        $projects = Project::visibleTo(Auth::user())
            ->latest()
            ->get();

        return view('livewire.projects.index', [
            'projects' => $projects,
            'statuses' => OptionList::optionsFor('project_statuses'),
        ])->layout('layouts.app');
    }
}
