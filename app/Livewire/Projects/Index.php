<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public ?string $successMessage = null;
    public ?string $errorMessage   = null;

    public const STATUSES = ['active', 'completed', 'archived'];

    public function saveName(int $id, string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            $this->errorMessage = 'Project name cannot be empty.';
            return;
        }

        $project = Auth::user()->projects()->findOrFail($id);

        try {
            $project->update(['name' => $name]);
            $this->successMessage = 'Project renamed.';
            $this->errorMessage   = null;
        } catch (\Exception $e) {
            $this->errorMessage   = 'Could not rename project: a project with that name may already exist.';
            $this->successMessage = null;
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            return;
        }

        $project = Auth::user()->projects()->findOrFail($id);
        $project->update(['status' => $status]);

        $this->successMessage = 'Status updated.';
        $this->errorMessage   = null;
    }

    public function deleteProject(int $id): void
    {
        $project = Auth::user()->projects()->findOrFail($id);
        $project->delete();

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
        $projects = Auth::user()
            ->projects()
            ->latest()
            ->get();

        return view('livewire.projects.index', [
            'projects' => $projects,
        ])->layout('layouts.app');
    }
}
