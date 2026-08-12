<?php

namespace App\Livewire\Samples;

use App\Imports\SamplesImport;
use App\Services\ActivityLogger;
use App\Services\SampleImporter;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Import extends Component
{
    use WithFileUploads;

    public $file = null;

    public ?int $total = null;
    public ?int $imported = null;
    public array $rowErrors = [];

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ];
    }

    public function import(): void
    {
        $this->validate();

        $sheet = new SamplesImport();
        Excel::import($sheet, $this->file->getRealPath());

        $result = (new SampleImporter())->import($sheet->rows ?? collect());

        $this->total = $result['total'];
        $this->imported = $result['imported'];
        $this->rowErrors = $result['errors'];

        if ($result['imported'] > 0) {
            ActivityLogger::importSamples($result['imported'], $result['total'], $this->file->getClientOriginalName());
        }

        $this->file = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.samples.import')->layout('layouts.app');
    }
}