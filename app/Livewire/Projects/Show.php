<?php

namespace App\Livewire\Projects;

use App\Models\Compound;
use App\Models\Project;
use App\Models\ProjectCompound;
use App\Models\Taxonomy;
use App\Services\CompoundMappingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Show extends Component
{
    use WithFileUploads;

    public const EXPORT_FIELD_GROUPS = [
        'Project Entry' => [
            'input_name'   => 'Input Name',
            'custom_name'  => 'Custom Name',
            'rt'           => 'RI',
            'mz'           => 'm/z',
            'is_mapped'    => 'Is Mapped',
            'is_duplicate' => 'Is Duplicate',
            'is_terpene'   => 'Is Terpene',
            'terpene_type' => 'Terpene Type',
        ],
        'Compound' => [
            'canonical_name'    => 'Canonical Name',
            'iupac_name'        => 'IUPAC Name',
            'molecular_formula' => 'Molecular Formula',
            'smiles'            => 'SMILES',
            'inchi'             => 'InChI',
            'inchikey'          => 'InChIKey',
            'pubchem_cid'       => 'PubChem CID',
            'cas'               => 'CAS',
            'hmdb_id'           => 'HMDB ID',
            'chebi_id'          => 'ChEBI ID',
            'description'       => 'Description',
        ],
        'Taxonomy' => [
            'taxonomy_kingdom'       => 'Kingdom',
            'taxonomy_superclass'    => 'Superclass',
            'taxonomy_class'         => 'Class',
            'taxonomy_subclass'      => 'Subclass',
            'taxonomy_direct_parent' => 'Direct Parent',
            'taxonomy_alt_parents'   => 'Alternative Parents',
        ],
        'Additional' => [
            'ri_polar' => 'RI polar (all values)',
            'synonyms' => 'Synonyms',
        ],
    ];

    public bool $showImportModal = false;

    #[Validate('nullable|file|mimes:csv,txt,xlsx,xls')]
    public $file = null;

    public array $manualCompounds = [];

    public Project $project;

    public string $search = '';
    public string $sortField = 'id';
    public string $sortDirection = 'asc';
    public int $perPage = 15;

    public bool $showProjectCompoundModal = false;
    public ?int $selectedProjectCompoundId = null;

    public bool   $showExportModal = false;
    public string $exportFormat    = 'csv';
    public array  $exportFields    = [
        'input_name', 'custom_name', 'rt', 'mz',
        'canonical_name', 'iupac_name', 'molecular_formula',
        'inchikey', 'pubchem_cid', 'cas', 'hmdb_id', 'ri_polar',
    ];
    public int $page = 1;
    public int $lastPage = 1;

    // Filters
    public bool $filterIsMapped   = false;
    public bool $filterHasPubchem = false;
    public bool $filterHasHmdb    = false;
    public bool $filterHasCas     = false;
    public bool $filterHasSmiles  = false;
    public bool $filterIsTerpene  = false;
    public string $filterKingdom  = '';
    public string $filterClass    = '';

    // Notification state
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function updated(string $property): void
    {
        if (!str_starts_with($property, 'filter')) {
            return;
        }
        if ($property === 'filterKingdom') {
            $this->filterClass = '';
        }
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->filterIsMapped   = false;
        $this->filterHasPubchem = false;
        $this->filterHasHmdb    = false;
        $this->filterHasCas     = false;
        $this->filterHasSmiles  = false;
        $this->filterIsTerpene  = false;
        $this->filterKingdom    = '';
        $this->filterClass      = '';
        $this->page             = 1;
    }

    private function computeIsTerpene(Compound $compound): bool
    {
        $raw = $compound->taxonomy?->raw_json ?? '';
        return stripos((string) $raw, 'terpen') !== false;
    }

    private function computeTerpeneType(Compound $compound): ?string
    {
        $formula = $compound->molecular_formula ?? '';
        if (!$formula || !preg_match('/C(\d+)/', $formula, $m)) {
            return null;
        }
        $carbons   = (int) $m[1];
        $isAlcohol = str_contains($formula, 'O');

        if ($isAlcohol) {
            return match($carbons) {
                10 => 'Monoterpenol',
                15 => 'Sesquiterpenol',
                20 => 'Diterpenol',
                default => 'Other',
            };
        }

        return match($carbons) {
            10 => 'Monoterpene',
            15 => 'Sesquiterpene',
            20 => 'Diterpene',
            default => 'Other',
        };
    }

    private function normalizeOptionalNumber(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace(',', '.', (string) $value));

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function createProjectCompoundFromInput(?string $name, mixed $ri = null, mixed $mz = null): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return false;
        }

        $projectCompound = new ProjectCompound();
        $projectCompound->project_id = $this->project->id;
        $projectCompound->compound_id = null;
        $projectCompound->input_name  = $name;
        $projectCompound->custom_name = $name;
        $projectCompound->rt = $this->normalizeOptionalNumber($ri);
        $projectCompound->mz = $this->normalizeOptionalNumber($mz);
        $projectCompound->is_mapped = false;
        $projectCompound->is_duplicate = false;
        $projectCompound->save();

        return $projectCompound->exists;
    }

    public function mapLocal(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $rows = $this->project->projectCompounds()
            ->whereNull('compound_id')
            ->get();

        $mapped = 0;

        foreach ($rows as $row) {
            $input = trim($row->custom_name);

            if (!$input) {
                continue;
            }

            $normalized = Str::lower($input);

            $compound = Compound::whereRaw('LOWER(canonical_name) = ?', [$normalized])->first();

            if (!$compound) {
                $compound = Compound::whereHas('synonyms', function ($q) use ($normalized) {
                    $q->whereRaw('LOWER(name) = ?', [$normalized]);
                })->first();
            }

            if (!$compound) {
                $compound = Compound::whereRaw('LOWER(canonical_name) LIKE ?', ["%{$normalized}%"])
                    ->first();
            }

            if ($compound) {
                $compound->load('taxonomy');
                $isTerpene = $this->computeIsTerpene($compound);
                $row->update([
                    'compound_id'  => $compound->id,
                    'is_mapped'    => true,
                    'is_terpene'   => $isTerpene,
                    'terpene_type' => $isTerpene ? $this->computeTerpeneType($compound) : null,
                ]);
                $mapped++;
            }
        }

        $total = $rows->count();

        $this->detectDuplicates();

        if ($mapped === 0) {
            $this->errorMessage = 'No compounds could be matched in the local database.';
        } else {
            $this->successMessage = "Mapped {$mapped} of {$total} compound(s) using the local database.";
        }
    }

    public function mapSingle(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage   = null;

        $row  = $this->project->projectCompounds()->findOrFail($id);
        $name = $row->custom_name;

        try {
            $mapped = app(CompoundMappingService::class)->mapProjectCompound($row);

            if ($mapped) {
                $row->refresh();
                if ($row->compound_id) {
                    $compound  = $row->compound()->with('taxonomy')->first();
                    $isTerpene = $this->computeIsTerpene($compound);
                    $row->update([
                        'is_terpene'   => $isTerpene,
                        'terpene_type' => $isTerpene ? $this->computeTerpeneType($compound) : null,
                    ]);
                }
                $this->detectDuplicates();
                $this->project->refresh();
                $this->successMessage = "'{$name}' mapped successfully.";
            } else {
                $this->errorMessage = "Could not map '{$name}' — not found in any source.";
            }
        } catch (\Throwable $e) {
            Log::error('mapSingle failed', [
                'project_compound_id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = "Mapping failed: " . $e->getMessage();
        }
    }

    public function detectDuplicates(): void
    {
        $duplicateCids = $this->project->projectCompounds()
            ->whereNotNull('compound_id')
            ->select('compound_id')
            ->groupBy('compound_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('compound_id');

        if ($duplicateCids->isEmpty()) {
            $this->project->projectCompounds()
                ->where('is_duplicate', true)
                ->update(['is_duplicate' => false]);
            return;
        }

        $this->project->projectCompounds()
            ->whereIn('compound_id', $duplicateCids)
            ->update(['is_duplicate' => true]);

        $this->project->projectCompounds()
            ->where(function ($q) use ($duplicateCids) {
                $q->whereNull('compound_id')
                  ->orWhereNotIn('compound_id', $duplicateCids);
            })
            ->update(['is_duplicate' => false]);
    }

    public function importIntoProject(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $extension = strtolower($this->file->getClientOriginalExtension());
        $path = $this->file->getRealPath();

        try {
            if (in_array($extension, ['csv', 'txt'], true)) {
                $rows = [];
                $handle = fopen($path, 'r');

                if ($handle === false) {
                    $this->addError('file', 'Unable to read the uploaded file.');
                    return;
                }

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rows[] = $row;
                }

                fclose($handle);
            } else {
                $spreadsheet = IOFactory::load($path);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                $rows = array_map('array_values', $rows);
            }

            $rows = array_values(array_filter($rows, function ($row) {
                foreach ((array) $row as $cell) {
                    if (trim((string) $cell) !== '') {
                        return true;
                    }
                }

                return false;
            }));

            if (count($rows) === 0) {
                $this->addError('file', 'The uploaded file is empty.');
                return;
            }

            $header = array_map(function ($h) {
                return strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF"));
            }, $rows[0]);

            $knownNameColumns = ['name', 'compound', 'compound_name', 'input_name', 'custom_name', 'canonical_name'];
            $hasHeader = count(array_intersect($header, $knownNameColumns)) > 0;

            $created = 0;

            if ($hasHeader) {
                unset($rows[0]);

                foreach ($rows as $row) {
                    $row = array_pad(array_values((array) $row), count($header), null);
                    $row = array_combine($header, array_slice($row, 0, count($header)));

                    if ($row === false) {
                        continue;
                    }

                    $name = $row['name']
                        ?? $row['compound']
                        ?? $row['compound_name']
                        ?? $row['input_name']
                        ?? $row['custom_name']
                        ?? $row['canonical_name']
                        ?? null;

                    $ri = $row['ri'] ?? $row['rt'] ?? null;
                    $mz = $row['mz'] ?? $row['m/z'] ?? null;

                    if ($this->createProjectCompoundFromInput($name, $ri, $mz)) {
                        $created++;
                    }
                }
            } else {
                foreach ($rows as $row) {
                    $row = array_values((array) $row);

                    if ($this->createProjectCompoundFromInput(
                        $row[0] ?? null,
                        $row[1] ?? null,
                        $row[2] ?? null,
                    )) {
                        $created++;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Project compound import failed', [
                'project_id' => $this->project->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('file', 'Unable to import this file: ' . $e->getMessage());
            return;
        }

        if ($created === 0) {
            $this->addError('file', 'No valid compounds were found. Add a name column, or put names in the first column.');
            return;
        }

        $this->reset('file');
        $this->showImportModal = false;
        $this->resetErrorBag();
        $this->page = 1;
        $this->project->refresh();
        $this->successMessage = "Successfully imported {$created} compound(s).";
    }

    public function addManualRow(): void
    {
        $this->manualCompounds[] = [
            'name' => '',
            'ri' => null,
            'mz' => null,
        ];
    }

    public function removeManualRow(int $index): void
    {
        unset($this->manualCompounds[$index]);
        $this->manualCompounds = array_values($this->manualCompounds);
    }

    /**
     * Rows are passed directly from Alpine — we never rely on wire:model
     * syncing nested array properties, which is unreliable in Livewire 3.
     */
    public function saveManualCompounds(array $rows = []): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $created = 0;

            foreach ($rows as $row) {
                if ($this->createProjectCompoundFromInput(
                    $row['name'] ?? null,
                    $row['ri'] ?? null,
                    $row['mz'] ?? null,
                )) {
                    $created++;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Manual project compound save failed', [
                'project_id' => $this->project->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('manualCompounds', 'Unable to save compounds: ' . $e->getMessage());
            return;
        }

        if ($created === 0) {
            $this->addError('manualCompounds', 'Add at least one compound name.');
            return;
        }

        $this->showImportModal = false;
        $this->resetErrorBag();
        $this->page = 1;
        $this->project->refresh();
        $this->successMessage = "Successfully saved {$created} compound(s).";
    }

    public function updateCustomName(int $id, string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            $this->errorMessage   = 'Custom name cannot be empty.';
            $this->successMessage = null;
            return;
        }

        $this->project->projectCompounds()->findOrFail($id)->update(['custom_name' => $name]);

        $this->successMessage = 'Custom name updated.';
        $this->errorMessage   = null;
    }

    public function updateRt(int $id, string $value): void
    {
        $this->project->projectCompounds()->findOrFail($id)->update([
            'rt' => $this->normalizeOptionalNumber($value),
        ]);

        $this->successMessage = 'RI updated.';
        $this->errorMessage   = null;
    }

    public function updateMz(int $id, string $value): void
    {
        $this->project->projectCompounds()->findOrFail($id)->update([
            'mz' => $this->normalizeOptionalNumber($value),
        ]);

        $this->successMessage = 'm/z updated.';
        $this->errorMessage   = null;
    }

    public function updateNotes(int $id, string $value): void
    {
        $value = trim($value);
        $this->project->projectCompounds()->findOrFail($id)->update([
            'notes' => $value === '' ? null : $value,
        ]);
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function goToCompoundsPage(int $pageNumber): void
    {
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        if ($pageNumber > $this->lastPage) {
            $pageNumber = $this->lastPage;
        }

        $this->page = $pageNumber;
    }

    public function previousCompoundsPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextCompoundsPage(): void
    {
        if ($this->page < $this->lastPage) {
            $this->page++;
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->page = 1;
    }

    public function openProjectCompoundModal(int $id): void
    {
        $this->selectedProjectCompoundId = $id;
        $this->showProjectCompoundModal  = true;
    }

    public function closeProjectCompoundModal(): void
    {
        $this->showProjectCompoundModal  = false;
        $this->selectedProjectCompoundId = null;
    }

    public function openExportModal(): void
    {
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
        $this->resetErrorBag('exportFields');
    }

    public function selectAllExportFields(): void
    {
        $this->exportFields = collect(self::EXPORT_FIELD_GROUPS)
            ->flatMap(fn($g) => array_keys($g))
            ->values()
            ->all();
    }

    public function clearExportFields(): void
    {
        $this->exportFields = [];
    }

    public function export(): StreamedResponse
    {
        $fields = array_values(array_filter($this->exportFields));

        if (empty($fields)) {
            $this->addError('exportFields', 'Select at least one field to export.');
            return response()->streamDownload(fn() => null, 'empty.csv');
        }

        $allFieldLabels = collect(self::EXPORT_FIELD_GROUPS)->flatMap(fn($g) => $g);
        $headers = collect($fields)->map(fn($f) => $allFieldLabels->get($f, $f))->all();

        $rows = $this->project->projectCompounds()
            ->with(['compound', 'compound.taxonomy', 'compound.retentionIndices.source', 'compound.synonyms'])
            ->get();

        $data = $rows->map(fn($pc) => $this->buildExportRow($pc, $fields))->all();

        $filename = Str::slug($this->project->name) . '-' . now()->format('Y-m-d');

        if ($this->exportFormat === 'xlsx') {
            return $this->exportAsXlsx("{$filename}.xlsx", $headers, $data);
        }

        $separator   = $this->exportFormat === 'txt' ? "\t" : ',';
        $contentType = $this->exportFormat === 'txt' ? 'text/plain' : 'text/csv';

        return response()->streamDownload(function () use ($headers, $data, $separator) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel / Numbers compatibility)
            fputcsv($out, $headers, $separator);
            foreach ($data as $row) {
                fputcsv($out, $row, $separator);
            }
            fclose($out);
        }, "{$filename}.{$this->exportFormat}", ['Content-Type' => $contentType]);
    }

    private function buildExportRow(ProjectCompound $pc, array $fields): array
    {
        $c = $pc->compound;

        return collect($fields)->map(fn($field) => match ($field) {
            'input_name'             => (string) ($pc->input_name ?? ''),
            'custom_name'            => (string) ($pc->custom_name ?? ''),
            'rt'                     => $pc->rt !== null ? (string) $pc->rt : '',
            'mz'                     => $pc->mz !== null ? (string) $pc->mz : '',
            'is_mapped'              => $pc->is_mapped    ? 'Yes' : 'No',
            'is_duplicate'           => $pc->is_duplicate ? 'Yes' : 'No',
            'is_terpene'             => $pc->is_terpene   ? 'Yes' : 'No',
            'terpene_type'           => (string) ($pc->terpene_type ?? ''),
            'canonical_name'         => (string) ($c?->canonical_name    ?? ''),
            'iupac_name'             => (string) ($c?->iupac_name         ?? ''),
            'molecular_formula'      => (string) ($c?->molecular_formula  ?? ''),
            'smiles'                 => (string) ($c?->smiles              ?? ''),
            'inchi'                  => (string) ($c?->inchi               ?? ''),
            'inchikey'               => (string) ($c?->inchikey            ?? ''),
            'pubchem_cid'            => (string) ($c?->pubchem_cid         ?? ''),
            'cas'                    => (string) ($c?->cas                 ?? ''),
            'hmdb_id'                => (string) ($c?->hmdb_id             ?? ''),
            'chebi_id'               => (string) ($c?->chebi_id            ?? ''),
            'description'            => (string) ($c?->description         ?? ''),
            'taxonomy_kingdom'       => (string) ($c?->taxonomy?->kingdom        ?? ''),
            'taxonomy_superclass'    => (string) ($c?->taxonomy?->superclass     ?? ''),
            'taxonomy_class'         => (string) ($c?->taxonomy?->{'class'}      ?? ''),
            'taxonomy_subclass'      => (string) ($c?->taxonomy?->subclass       ?? ''),
            'taxonomy_direct_parent' => (string) ($c?->taxonomy?->direct_parent  ?? ''),
            'taxonomy_alt_parents'   => (string) ($c?->taxonomy?->alternative_parents ?? ''),
            'ri_polar'               => $c?->retentionIndices
                ?->where('is_polar', true)
                ->map(fn($ri) => number_format((float) $ri->value, 1) . ' (' . ($ri->source?->name ?? '?') . ')')
                ->join('; ') ?? '',
            'synonyms'               => $c?->synonyms
                ->map(fn($s) => $s->name)
                ->join('; ') ?? '',
            default => '',
        })->all();
    }

    private function exportAsXlsx(string $filename, array $headers, array $data): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->fromArray([$headers, ...$data], null, 'A1');

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        $colCount = count($headers);
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->manualCompounds = [
            [
                'name' => '',
                'ri' => null,
                'mz' => null,
            ],
        ];
        $this->resetErrorBag();
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset('file');
        $this->manualCompounds = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = $this->project->projectCompounds()
            ->with(['compound', 'compound.taxonomy', 'compound.retentionIndices.source', 'compound.projects']);

        if ($this->search !== '') {
            $search = strtolower(trim($this->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(custom_name) LIKE ?', ["%{$search}%"])
                ->orWhereHas('compound', function ($cq) use ($search) {
                    $cq->whereRaw('LOWER(canonical_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(iupac_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(inchikey) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        $query
            ->when($this->filterIsMapped,   fn($q) => $q->where('is_mapped', true))
            ->when($this->filterHasPubchem, fn($q) => $q->whereHas('compound', fn($cq) => $cq->whereNotNull('pubchem_cid')))
            ->when($this->filterHasHmdb,    fn($q) => $q->whereHas('compound', fn($cq) => $cq->whereNotNull('hmdb_id')))
            ->when($this->filterHasCas,     fn($q) => $q->whereHas('compound', fn($cq) => $cq->whereNotNull('cas')))
            ->when($this->filterHasSmiles,  fn($q) => $q->whereHas('compound', fn($cq) => $cq->whereNotNull('smiles')))
            ->when($this->filterIsTerpene, fn($q) => $q->where('project_compounds.is_terpene', true))
            ->when($this->filterKingdom !== '', fn($q) => $q->whereHas('compound.taxonomy', fn($tq) =>
                $tq->where('kingdom', $this->filterKingdom)
            ))
            ->when($this->filterClass !== '', fn($q) => $q->whereHas('compound.taxonomy', fn($tq) =>
                $tq->where('class', $this->filterClass)
            ));

        if (in_array($this->sortField, ['canonical_name', 'iupac_name', 'molecular_formula'])) {
            $query->leftJoin('compounds', 'project_compounds.compound_id', '=', 'compounds.id')
                ->orderBy("compounds.{$this->sortField}", $this->sortDirection)
                ->select('project_compounds.*');
        } elseif ($this->sortField === 'projects_count') {
            $query->addSelect(DB::raw('(SELECT COUNT(DISTINCT project_id) FROM project_compounds AS pc2 WHERE pc2.compound_id = project_compounds.compound_id) AS projects_unique_count'))
                ->orderBy('projects_unique_count', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $compounds = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        $this->lastPage = max(1, $compounds->lastPage());

        if ($this->page > $this->lastPage) {
            $this->page = $this->lastPage;
            $compounds = $query->paginate($this->perPage, ['*'], 'page', $this->page);
        }

        $projectCompoundIds = $this->project->projectCompounds()
            ->whereNotNull('compound_id')
            ->pluck('compound_id');

        $kingdoms = Taxonomy::whereIn('compound_id', $projectCompoundIds)
            ->whereNotNull('kingdom')
            ->distinct()
            ->orderBy('kingdom')
            ->pluck('kingdom');

        $classes = Taxonomy::whereIn('compound_id', $projectCompoundIds)
            ->whereNotNull('class')
            ->when($this->filterKingdom !== '', fn($q) => $q->where('kingdom', $this->filterKingdom))
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        $activeFilterCount = (int) $this->filterIsMapped
            + (int) $this->filterHasPubchem
            + (int) $this->filterHasHmdb
            + (int) $this->filterHasCas
            + (int) $this->filterHasSmiles
            + (int) $this->filterIsTerpene
            + (int) ($this->filterKingdom !== '')
            + (int) ($this->filterClass !== '');

        $duplicateGroups = $this->project->projectCompounds()
            ->whereNotNull('compound_id')
            ->where('is_duplicate', true)
            ->select(['compound_id', 'custom_name'])
            ->get()
            ->groupBy('compound_id')
            ->map(fn($rows) => $rows->pluck('custom_name')->toArray())
            ->all();

        $selectedProjectCompound = $this->selectedProjectCompoundId
            ? ProjectCompound::with([
                'compound.synonyms.source',
                'compound.retentionIndices.source',
                'compound.taxonomy',
                'compound.diseases',
                'compound.ontologies',
                'compound.biomarkers',
                'compound.projects',
            ])->find($this->selectedProjectCompoundId)
            : null;

        $exportFieldGroups = self::EXPORT_FIELD_GROUPS;

        return view('livewire.projects.show', compact(
            'compounds',
            'duplicateGroups',
            'kingdoms',
            'classes',
            'activeFilterCount',
            'selectedProjectCompound',
            'exportFieldGroups',
        ))->layout('layouts.app');
    }
}