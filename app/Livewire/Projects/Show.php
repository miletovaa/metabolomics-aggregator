<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The project dashboard — project info plus its related Samples, Samplings, and Experiments.
 * Compounds are never shown here: they only ever appear on an experiment's results page
 * (App\Livewire\Experiments\Results), scoped to one analysis run.
 */
class Show extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless(Project::visibleTo(Auth::user())->whereKey($project->id)->exists(), 404);

        $this->project = $project;
    }

    public function render()
    {
        $relatedSamples = $this->project->samples()->latest('id')->limit(5)->get();
        $relatedSamplesCount = $this->project->samples()->count();
        $relatedSamplings = $this->project->samplings()->latest('date_of_sampling')->limit(5)->get();
        $relatedSamplingsCount = $this->project->samplings()->count();
        $relatedExperiments = $this->project->experiments()->latest('id')->limit(5)->get();
        $relatedExperimentsCount = $this->project->experiments()->count();

        return view('livewire.projects.show', [
            'relatedSamples' => $relatedSamples,
            'relatedSamplesCount' => $relatedSamplesCount,
            'relatedSamplings' => $relatedSamplings,
            'relatedSamplingsCount' => $relatedSamplingsCount,
            'relatedExperiments' => $relatedExperiments,
            'relatedExperimentsCount' => $relatedExperimentsCount,
        ])->layout('layouts.app');
    }
}
