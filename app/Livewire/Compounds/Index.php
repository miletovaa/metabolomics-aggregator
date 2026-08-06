<?php

namespace App\Livewire\Compounds;

use App\Jobs\SyncCompoundsJob;
use App\Models\Compound;
use App\Models\Taxonomy;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';
    public string $sortField = 'id';
    public string $sortDirection = 'asc';
    public int $perPage = 15;

    public bool $showModal = false;
    public ?int $selectedCompoundId = null;
    public int $page = 1;
    public int $lastPage = 1;

    // Filters
    public bool $filterHasPubchem = false;
    public bool $filterHasHmdb    = false;
    public bool $filterHasCas     = false;
    public bool $filterHasSmiles  = false;
    public bool $filterIsTerpene  = false;
    public string $filterKingdom  = '';
    public string $filterClass    = '';

    public string $syncStatus    = 'idle';
    public int    $syncProcessed = 0;
    public int    $syncTotal     = 0;
    public string $syncCurrent   = '';
    public int    $syncEnriched  = 0;
    public int    $syncFailed    = 0;

    protected $queryString = [
        'search'           => ['except' => ''],
        'sortField'        => ['except' => 'id'],
        'sortDirection'    => ['except' => 'asc'],
        'perPage'          => ['except' => 15],
        'page'             => ['except' => 1],
        'filterHasPubchem' => ['except' => false],
        'filterHasHmdb'    => ['except' => false],
        'filterHasCas'     => ['except' => false],
        'filterHasSmiles'  => ['except' => false],
        'filterIsTerpene'  => ['except' => false],
        'filterKingdom'    => ['except' => ''],
        'filterClass'      => ['except' => ''],
    ];

    public function mount(): void
    {
        $progress = Cache::get(SyncCompoundsJob::cacheKey());
        if ($progress && in_array($progress['status'] ?? '', ['running', 'done', 'error'], true)) {
            $this->applyProgressPayload($progress);
        }
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
        $this->filterHasPubchem = false;
        $this->filterHasHmdb    = false;
        $this->filterHasCas     = false;
        $this->filterHasSmiles  = false;
        $this->filterIsTerpene  = false;
        $this->filterKingdom    = '';
        $this->filterClass      = '';
        $this->page             = 1;
    }

    public function sync(): void
    {
        if ($this->syncStatus === 'running') {
            return;
        }

        $total = Compound::whereNull('pubchem_cid')->count()
               + Compound::whereNotNull('pubchem_cid')->count()
               + Compound::count()
               + Compound::whereNull('description')->count()
               + Compound::whereNotNull('inchi')
                   ->whereDoesntHave('retentionIndices', fn($q) => $q->where('is_polar', true))->count()
               + Compound::whereNotNull('inchikey')
                   ->whereDoesntHave('taxonomy')->count();

        if ($total === 0) {
            return;
        }

        SyncCompoundsJob::dispatch();

        Cache::put(
            SyncCompoundsJob::cacheKey(),
            ['status' => 'running', 'processed' => 0, 'total' => $total, 'current' => 'Queued…', 'enriched' => 0, 'failed' => 0],
            now()->addHours(3),
        );

        $this->syncStatus    = 'running';
        $this->syncProcessed = 0;
        $this->syncTotal     = $total;
        $this->syncCurrent   = 'Queued…';
        $this->syncEnriched  = 0;
        $this->syncFailed    = 0;
    }

    public function pollSyncProgress(): void
    {
        if ($this->syncStatus === 'idle') {
            return;
        }

        $progress = Cache::get(SyncCompoundsJob::cacheKey());
        if (!$progress) {
            return;
        }

        $this->applyProgressPayload($progress);
    }

    private function applyProgressPayload(array $p): void
    {
        $this->syncStatus    = $p['status']    ?? 'running';
        $this->syncProcessed = (int) ($p['processed'] ?? 0);
        $this->syncTotal     = (int) ($p['total']     ?? 0);
        $this->syncCurrent   = $p['current']   ?? '';
        $this->syncEnriched  = (int) ($p['enriched']  ?? 0);
        $this->syncFailed    = (int) ($p['failed']    ?? 0);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->page = 1;
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
        $this->page = 1;
    }

    public function goToPageNumber(int $pageNumber): void
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
        $this->goToPageNumber($this->page - 1);
    }

    public function nextCompoundsPage(): void
    {
        $this->goToPageNumber($this->page + 1);
    }

    public function openCompoundModal(int $compoundId): void
    {
        $this->selectedCompoundId = $compoundId;
        $this->showModal = true;
    }

    public function closeCompoundModal(): void
    {
        $this->showModal = false;
        $this->selectedCompoundId = null;
    }

    public function updateDescription(int $id, string $value): void
    {
        $value = trim($value);
        $compound = Compound::findOrFail($id);
        $old = $compound->description;
        $new = $value === '' ? null : $value;
        $compound->update(['description' => $new]);
        if ($old !== $new) {
            ActivityLogger::editGlobalCompound($compound, ['description' => ($old ?: '—') . ' → ' . ($new ?: '—')]);
        }
    }

    public function render()
    {
        $query = Compound::query()
            ->with(['taxonomy', 'retentionIndices.source', 'projects'])
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($q) use ($search) {
                    $q->where('canonical_name', 'ilike', "%{$search}%")
                        ->orWhere('iupac_name', 'ilike', "%{$search}%")
                        ->orWhere('inchikey', 'ilike', "%{$search}%")
                        ->orWhere('hmdb_id', 'ilike', "%{$search}%")
                        ->orWhere('chebi_id', 'ilike', "%{$search}%")
                        ->orWhere('pubchem_cid', 'ilike', "%{$search}%")
                        ->orWhereHas('synonyms', function ($sq) use ($search) {
                            $sq->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($this->filterHasPubchem, fn($q) => $q->whereNotNull('pubchem_cid'))
            ->when($this->filterHasHmdb,    fn($q) => $q->whereNotNull('hmdb_id'))
            ->when($this->filterHasCas,     fn($q) => $q->whereNotNull('cas'))
            ->when($this->filterHasSmiles,  fn($q) => $q->whereNotNull('smiles'))
            ->when($this->filterIsTerpene,  fn($q) => $q->whereHas('taxonomy', fn($tq) =>
                $tq->where('kingdom', 'Terpenoids and polyketides')
                   ->orWhere('superclass', 'ilike', '%terpenoid%')
            ))
            ->when($this->filterKingdom !== '', fn($q) => $q->whereHas('taxonomy', fn($tq) =>
                $tq->where('kingdom', $this->filterKingdom)
            ))
            ->when($this->filterClass !== '', fn($q) => $q->whereHas('taxonomy', fn($tq) =>
                $tq->where('class', $this->filterClass)
            ))
            ->when(
                $this->sortField === 'projects_count',
                fn($q) => $q
                    ->addSelect(DB::raw('(SELECT COUNT(DISTINCT project_id) FROM project_compounds WHERE project_compounds.compound_id = compounds.id) AS projects_unique_count'))
                    ->orderBy('projects_unique_count', $this->sortDirection),
                fn($q) => $q->orderBy($this->sortField, $this->sortDirection),
            );

        $compounds = $query->paginate($this->perPage, ['*'], 'compoundsPage', $this->page);
        $this->lastPage = max(1, $compounds->lastPage());

        if ($this->page > $this->lastPage) {
            $this->page = $this->lastPage;
            $compounds = $query->paginate($this->perPage, ['*'], 'compoundsPage', $this->page);
        }

        $kingdoms = Taxonomy::whereNotNull('kingdom')
            ->distinct()
            ->orderBy('kingdom')
            ->pluck('kingdom');

        $classes = Taxonomy::whereNotNull('class')
            ->when($this->filterKingdom !== '', fn($q) => $q->where('kingdom', $this->filterKingdom))
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        $activeFilterCount = (int) $this->filterHasPubchem
            + (int) $this->filterHasHmdb
            + (int) $this->filterHasCas
            + (int) $this->filterHasSmiles
            + (int) $this->filterIsTerpene
            + (int) ($this->filterKingdom !== '')
            + (int) ($this->filterClass !== '');

        $selectedCompound = $this->selectedCompoundId
            ? Compound::query()
                ->with([
                    'synonyms.source',
                    'retentionIndices.source',
                    'taxonomy',
                    'projects',
                    'diseases',
                    'ontologies',
                    'biomarkers',
                ])
                ->find($this->selectedCompoundId)
            : null;

        return view('livewire.compounds.index', [
            'compounds'         => $compounds,
            'selectedCompound'  => $selectedCompound,
            'kingdoms'          => $kingdoms,
            'classes'           => $classes,
            'activeFilterCount' => $activeFilterCount,
        ])->layout('layouts.app');
    }
}
