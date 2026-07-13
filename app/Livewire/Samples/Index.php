<?php

namespace App\Livewire\Samples;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.samples.index')->layout('layouts.app');
    }
}
