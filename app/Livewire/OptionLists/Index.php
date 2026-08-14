<?php

namespace App\Livewire\OptionLists;

use App\Models\OptionList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('options', 'view'), 403);
    }

    public function render()
    {
        $lists = OptionList::withCount('values')
            ->with('parentList')
            ->orderBy('name')
            ->get();

        return view('livewire.option-lists.index', [
            'lists' => $lists,
        ])->layout('layouts.app');
    }
}
