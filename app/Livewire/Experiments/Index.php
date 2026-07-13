<?php

namespace App\Livewire\Experiments;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.experiments.index')->layout('layouts.app');
    }
}
