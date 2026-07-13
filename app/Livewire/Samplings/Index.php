<?php

namespace App\Livewire\Samplings;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.samplings.index')->layout('layouts.app');
    }
}
