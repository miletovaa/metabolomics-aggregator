<?php

namespace App\Livewire\Dashboard;

use App\Models\Compound;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.dashboard.index', [
            'projectsCount' => Auth::user()->projects()->count(),
            'compoundsCount' => Compound::count(),
        ])->layout('layouts.app');
    }
}
