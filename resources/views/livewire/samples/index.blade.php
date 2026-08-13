<div
    class="max-w-7xl mx-auto px-4 py-6"
    x-data="{ filtersOpen: {{ $activeFilterCount > 0 ? 'true' : 'false' }} }"
>

    {{-- Toast notification --}}
    @if($successMessage)
        <div
            x-data="{ visible: true }"
            x-init="setTimeout(() => { visible = false; $wire.dismissNotification() }, 3500)"
            x-show="visible"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium bg-green-50 text-green-800 border border-green-200"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $successMessage }}
            <button wire:click="dismissNotification" class="ml-1 opacity-60 hover:opacity-100">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Samples</h1>
            <p class="text-sm text-gray-500 mt-1">Physical samples currently tracked in storage.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('samples.import') }}" wire:navigate class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                Import
            </a>
            <a href="{{ route('samples.create') }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm">
                + Log Sample
            </a>
        </div>
    </div>

    <div class="flex items-center gap-3 mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by lab ID, external ID, or matrix…"
            class="w-full md:w-80 border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200"
        >

        {{-- Filters toggle --}}
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 focus:outline-none whitespace-nowrap"
            :class="filtersOpen ? 'bg-gray-100' : ''"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Filters
            @if($activeFilterCount > 0)
                <span class="rounded-full bg-blue-500 text-white text-xs leading-none px-1.5 py-0.5">{{ $activeFilterCount }}</span>
            @endif
        </button>
    </div>

    {{-- Filter panel --}}
    <div
        x-show="filtersOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200"
        style="display: none"
    >
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <p class="text-xs text-gray-500 mb-1">Project</p>
                <select wire:model.live="filterProjectId" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">All projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Analyst</p>
                <select wire:model.live="filterAnalystId" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">All analysts</option>
                    @foreach($analysts as $analyst)
                        <option value="{{ $analyst->id }}">{{ $analyst->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Group</p>
                <select wire:model.live="filterGroup" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">All groups</option>
                    @foreach($groups as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Subgroup</p>
                <select wire:model.live="filterSubgroup" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-40 disabled:cursor-not-allowed" @disabled(empty($subgroupOptions))>
                    <option value="">All subgroups</option>
                    @foreach($subgroupOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Storage condition</p>
                <select wire:model.live="filterStorageCondition" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">All conditions</option>
                    @foreach($storageConditions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Date received from</p>
                <input type="date" wire:model.live="filterDateFrom" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Date received to</p>
                <input type="date" wire:model.live="filterDateTo" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
            </div>

            @if($activeFilterCount > 0)
                <button wire:click="clearFilters" class="flex items-center gap-1 text-sm text-red-500 hover:text-red-700 px-2 py-1.5 rounded-lg hover:bg-red-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear ({{ $activeFilterCount }})
                </button>
            @endif
        </div>
    </div>

    @php
        $sortIndicator = fn (string $field) => $sortField === $field
            ? ($sortDirection === 'asc' ? '↑' : '↓')
            : '↕';
    @endphp

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('lab_sample_id')">Lab ID <span class="{{ $sortField === 'lab_sample_id' ? '' : 'text-gray-400' }}">{{ $sortIndicator('lab_sample_id') }}</span></th>
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('external_id')">External ID <span class="{{ $sortField === 'external_id' ? '' : 'text-gray-400' }}">{{ $sortIndicator('external_id') }}</span></th>
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('sample_group')">Group <span class="{{ $sortField === 'sample_group' ? '' : 'text-gray-400' }}">{{ $sortIndicator('sample_group') }}</span></th>
                    <th class="p-3">Subgroup</th>
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('date_received')">Date received <span class="{{ $sortField === 'date_received' ? '' : 'text-gray-400' }}">{{ $sortIndicator('date_received') }}</span></th>
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('project')">Project <span class="{{ $sortField === 'project' ? '' : 'text-gray-400' }}">{{ $sortIndicator('project') }}</span></th>
                    <th class="p-3 cursor-pointer select-none" wire:click="sortBy('analyst')">Analyst <span class="{{ $sortField === 'analyst' ? '' : 'text-gray-400' }}">{{ $sortIndicator('analyst') }}</span></th>
                    <th class="p-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($samples as $sample)
                    <tr
                        wire:key="sample-{{ $sample->id }}"
                        x-on:click="Livewire.navigate('{{ route('samples.edit', $sample) }}')"
                        class="hover:bg-gray-50 cursor-pointer"
                    >
                        <td class="p-3 font-medium text-gray-900">
                            {{ $sample->lab_sample_id ?: '—' }}
                        </td>
                        <td class="p-3 text-gray-600">{{ $sample->external_id ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->groupLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->subgroupLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->date_received?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->project?->name ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->responsibleAnalyst?->name ?? '—' }}</td>
                        <td class="p-3 text-right">
                            <button
                                x-on:click.stop
                                wire:click="deleteSample({{ $sample->id }})"
                                wire:confirm="Delete this sample? This cannot be undone."
                                class="text-gray-400 hover:text-red-600"
                                title="Delete sample"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400">
                            No samples match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $samples->links() }}
    </div>
</div>
