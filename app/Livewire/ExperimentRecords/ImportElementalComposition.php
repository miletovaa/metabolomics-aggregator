<?php

namespace App\Livewire\ExperimentRecords;

use App\Imports\ElementalCompositionImport;
use App\Models\Experiment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ElementalCompositionImporter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportElementalComposition extends Component
{
    use WithFileUploads;

    public Experiment $experiment;

    public $file = null;

    public ?int $performedBy = null;

    public ?int $total = null;

    public ?int $importedSamples = null;

    public array $rowErrors = [];

    public function mount(Experiment $experiment): void
    {
        abort_unless(Experiment::visibleTo(Auth::user(), 'edit')->whereKey($experiment->id)->exists(), 404);

        $this->experiment = $experiment;
    }

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ];
    }

    public function import(): void
    {
        $this->validate();

        $sheet = new ElementalCompositionImport;
        Excel::import($sheet, $this->file->getRealPath());

        $result = (new ElementalCompositionImporter)->import($sheet->rows ?? collect(), $this->experiment, $this->performedBy);

        $this->total = $result['total'];
        $this->importedSamples = $result['importedSamples'];
        $this->rowErrors = $result['errors'];

        if ($result['importedSamples'] > 0) {
            ActivityLogger::importElementalComposition($this->experiment, $result['importedSamples'], $result['total'], $this->file->getClientOriginalName());
        }

        $this->file = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.experiment-records.import-elemental-composition', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
