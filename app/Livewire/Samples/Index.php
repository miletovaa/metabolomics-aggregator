<?php

namespace App\Livewire\Samples;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $successMessage = null;

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    private const SORT_FIELDS = [
        'lab_sample_id', 'external_id', 'sample_group', 'date_received',
        'storage_condition', 'project', 'analyst', 'created_at',
    ];

    // Filters
    public string $filterProjectId = '';
    public string $filterAnalystId = '';
    public string $filterGroup = '';
    public string $filterSubgroup = '';
    public string $filterStorageCondition = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'filterProjectId' => ['except' => ''],
        'filterAnalystId' => ['except' => ''],
        'filterGroup' => ['except' => ''],
        'filterSubgroup' => ['except' => ''],
        'filterStorageCondition' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGroup(): void
    {
        $this->filterSubgroup = '';
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filter')) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORT_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterProjectId = '';
        $this->filterAnalystId = '';
        $this->filterGroup = '';
        $this->filterSubgroup = '';
        $this->filterStorageCondition = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function deleteSample(int $id): void
    {
        $sample = Sample::visibleTo(Auth::user())->findOrFail($id);
        $name = $sample->lab_sample_id ?: ($sample->external_id ?: "#{$sample->id}");
        $sample->delete();
        ActivityLogger::deleteSample($name, $id);
        $this->successMessage = 'Sample deleted.';
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        $user = Auth::user();

        $query = Sample::query()
            ->visibleTo($user)
            ->with(['project', 'responsibleAnalyst'])
            ->when($this->search !== '', function ($query) {
                $search = strtolower(trim($this->search));
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(lab_sample_id) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(external_id) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(matrix_group) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($this->filterProjectId !== '', fn ($q) => $q->where('project_id', $this->filterProjectId))
            ->when($this->filterAnalystId !== '', fn ($q) => $q->where('responsible_analyst_id', $this->filterAnalystId))
            ->when($this->filterGroup !== '', fn ($q) => $q->where('sample_group', $this->filterGroup))
            ->when($this->filterSubgroup !== '', fn ($q) => $q->where('sample_subgroup', $this->filterSubgroup))
            ->when($this->filterStorageCondition !== '', fn ($q) => $q->where('storage_condition', $this->filterStorageCondition))
            ->when($this->filterDateFrom !== '', fn ($q) => $q->whereDate('date_received', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo !== '', fn ($q) => $q->whereDate('date_received', '<=', $this->filterDateTo));

        if ($this->sortField === 'project') {
            $query->leftJoin('projects', 'samples.project_id', '=', 'projects.id')
                ->select('samples.*')
                ->orderBy('projects.name', $this->sortDirection);
        } elseif ($this->sortField === 'analyst') {
            $query->leftJoin('users', 'samples.responsible_analyst_id', '=', 'users.id')
                ->select('samples.*')
                ->orderBy('users.name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $samples = $query->paginate(15);

        $activeFilterCount = (int) ($this->filterProjectId !== '')
            + (int) ($this->filterAnalystId !== '')
            + (int) ($this->filterGroup !== '')
            + (int) ($this->filterSubgroup !== '')
            + (int) ($this->filterStorageCondition !== '')
            + (int) ($this->filterDateFrom !== '')
            + (int) ($this->filterDateTo !== '');

        return view('livewire.samples.index', [
            'samples' => $samples,
            'projects' => Project::visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'analysts' => User::orderBy('name')->get(['id', 'name']),
            'groups' => Sample::GROUPS,
            'subgroupOptions' => Sample::SUBGROUPS[$this->filterGroup] ?? [],
            'storageConditions' => Sample::STORAGE_CONDITIONS,
            'activeFilterCount' => $activeFilterCount,
        ])->layout('layouts.app');
    }
}
