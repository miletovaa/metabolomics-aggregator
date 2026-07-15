<?php

namespace App\Livewire\Dashboard;

use App\Models\Compound;
use App\Models\Experiment;
use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.dashboard.index', [
            'projectsCount' => Auth::user()->projects()->count(),
            'experimentsCount' => Experiment::count(),
            'samplesCount' => Sample::count(),
            'samplingsCount' => Sampling::count(),
            'compoundsCount' => Compound::count(),
        ])->layout('layouts.app');
    }
}
