<?php

namespace App\Livewire\Samples;

use App\Imports\SamplesImport;
use App\Models\Sample;
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

    /** @var array<int, array{row: int, attributes: array, existing_id: int, existing_label: string, status: string}> */
    public array $duplicates = [];

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
        $this->duplicates = collect($result['duplicates'])->map(fn ($d) => [
            'row' => $d['row'],
            'attributes' => $d['attributes'],
            'existing_id' => $d['existing']->id,
            'existing_label' => $d['existing']->lab_sample_id ?: ($d['existing']->external_id ?: ('#' . $d['existing']->id)),
            'status' => 'pending',
        ])->all();

        if ($result['imported'] > 0) {
            ActivityLogger::importSamples($result['imported'], $result['total'], $this->file->getClientOriginalName());
        }

        $this->file = null;
        $this->resetValidation();
    }

    public function acceptDuplicate(int $index): void
    {
        if (! isset($this->duplicates[$index]) || $this->duplicates[$index]['status'] !== 'pending') {
            return;
        }

        $sample = Sample::create($this->duplicates[$index]['attributes']);
        ActivityLogger::createSample($sample);

        $this->duplicates[$index]['status'] = 'accepted';
        $this->imported++;
    }

    public function declineDuplicate(int $index): void
    {
        if (! isset($this->duplicates[$index]) || $this->duplicates[$index]['status'] !== 'pending') {
            return;
        }

        $this->duplicates[$index]['status'] = 'declined';
    }

    public function overrideDuplicate(int $index): void
    {
        if (! isset($this->duplicates[$index]) || $this->duplicates[$index]['status'] !== 'pending') {
            return;
        }

        $sample = Sample::findOrFail($this->duplicates[$index]['existing_id']);
        $sample->fill($this->duplicates[$index]['attributes']);
        $changes = ActivityLogger::diff($sample);
        $sample->save();

        if ($changes) {
            ActivityLogger::editSample($sample, $changes);
        }

        $this->duplicates[$index]['status'] = 'overridden';
        $this->imported++;
    }

    public function render()
    {
        return view('livewire.samples.import')->layout('layouts.app');
    }
}