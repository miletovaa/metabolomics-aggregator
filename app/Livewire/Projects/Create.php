<?php

namespace App\Livewire\Projects;

use App\Models\ProjectCompound;
use App\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Create extends Component
{
    use WithFileUploads;

    public string $name = '';
    public $file;

    public array $manualCompounds = [];

    public function addManualRow()
    {
        $this->manualCompounds[] = [
            'name' => '',
            'ri' => null,
            'mz' => null,
        ];
    }

    public function create()
    {
        $project = auth()->user()->projects()->create([
            'name' => $this->name,
        ]);
        ActivityLogger::createProject($project);

        // FILE IMPORT
        if ($this->file) {
            $this->importFile($project);
        }

        // MANUAL INPUT
        foreach ($this->manualCompounds as $row) {
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                continue; // skip empty rows
            }

            ProjectCompound::create([
                'project_id' => $project->id,
                'compound_id' => null,
                'custom_name' => $name,
                'rt' => $row['ri'] ?? null,
                'mz' => $row['mz'] ?? null,
                'is_mapped' => false,
            ]);
        }

        return redirect()->route('projects.show', $project);
    }

    private function importFile($project)
    {
        $extension = strtolower($this->file->getClientOriginalExtension());

        if ($extension === 'csv' || $extension === 'txt') {
            $rows = array_map('str_getcsv', file($this->file->getRealPath()));
        } else {
            $spreadsheet = IOFactory::load($this->file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();
        }

        if (count($rows) === 0) {
            return;
        }

        // normalize header
        $header = $this->normalizeHeader($rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

            $row = $this->combineRow($header, $row);

            $name = trim($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            ProjectCompound::create([
                'project_id' => $project->id,
                'compound_id' => null,
                'custom_name' => $name,
                'rt' => isset($row['ri']) && $row['ri'] !== '' ? (float) $row['ri'] : null,
                'mz' => isset($row['mz']) && $row['mz'] !== '' ? (float) $row['mz'] : null,
                'is_mapped' => false,
            ]);
        }
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(
            fn ($h) => strtolower(trim((string) $h)),
            $header
        );
    }

    private function combineRow(array $header, array $row): array
    {
        $combined = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $combined[$key] = $row[$index] ?? null;
        }

        return $combined;
    }

    public function render()
    {
        return view('livewire.projects.create')->layout('layouts.app');
    }
}