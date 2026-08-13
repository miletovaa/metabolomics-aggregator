<?php

namespace App\Livewire\Dashboard;

use App\Models\Compound;
use App\Models\Experiment;
use App\Models\Project;
use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $user = Auth::user();

        return view('livewire.dashboard.index', [
            'projectsCount' => Project::visibleTo($user)->count(),
            'experimentsCount' => Experiment::visibleTo($user)->count(),
            'samplesCount' => Sample::visibleTo($user)->count(),
            'samplingsCount' => Sampling::visibleTo($user)->count(),
            'compoundsCount' => Compound::count(),
        ])->layout('layouts.app');
    }
}
