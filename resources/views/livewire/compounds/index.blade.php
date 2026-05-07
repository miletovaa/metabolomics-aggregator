<div
    class="max-w-7xl mx-auto py-6"
    x-data="{
        colsOpen: false,
        filtersOpen: {{ $activeFilterCount > 0 ? 'true' : 'false' }},
        cols: {},
        colDefs: [
            { key: 'id',                    label: 'ID' },
            { key: 'canonical_name',        label: 'Canonical Name' },
            { key: 'iupac_name',            label: 'IUPAC Name' },
            { key: 'molecular_formula',     label: 'Formula' },
            { key: 'smiles',                label: 'SMILES' },
            { key: 'inchi',                 label: 'InChI' },
            { key: 'inchikey',              label: 'InChIKey' },
            { key: 'pubchem_cid',           label: 'PubChem CID' },
            { key: 'cas',                   label: 'CAS' },
            { key: 'hmdb_id',               label: 'HMDB' },
            { key: 'chebi_id',              label: 'ChEBI' },
            { key: 'ri_polar',              label: 'RI (polar)' },
            { key: 'taxonomy_kingdom',      label: 'Kingdom' },
            { key: 'taxonomy_superclass',   label: 'Superclass' },
            { key: 'taxonomy_class',        label: 'Class' },
            { key: 'taxonomy_subclass',     label: 'Subclass' },
            { key: 'taxonomy_direct_parent',label: 'Direct Parent' },
        ],
        init() {
            const defaults = { id: true, canonical_name: true, iupac_name: true, molecular_formula: true, smiles: false, inchi: false, inchikey: true, pubchem_cid: false, cas: true, hmdb_id: true, chebi_id: true, ri_polar: true, taxonomy_kingdom: true, taxonomy_superclass: false, taxonomy_class: false, taxonomy_subclass: false, taxonomy_direct_parent: false };
            const saved = JSON.parse(localStorage.getItem('compound_cols') || 'null');
            this.cols = { ...defaults, ...(saved || {}) };
        },
        saveCol() {
            localStorage.setItem('compound_cols', JSON.stringify(this.cols));
        }
    }"
>

    <div @if($syncStatus === 'running') wire:poll.2s="pollSyncProgress" @endif>
        <div class="flex justify-end items-end flex-col mb-2 gap-2">

            <button
                wire:click="sync"
                wire:loading.attr="disabled"
                wire:target="sync"
                @disabled($syncStatus === 'running')
                class="relative bg-blue-500 text-white px-3 py-1 rounded disabled:opacity-70 min-w-[11rem] tabular-nums"
                title="Enrich all compounds from PubChem (CID lookup + properties + synonyms)"
            >
                <span wire:loading wire:target="sync" class="flex items-center justify-center gap-1">
                    <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    Queuing…
                </span>
                <span wire:loading.remove wire:target="sync" class="flex items-center justify-center gap-1.5">
                    @if($syncStatus === 'running')
                        @php $pct = $syncTotal > 0 ? round($syncProcessed / $syncTotal * 100) : 0; @endphp
                        <svg class="animate-spin w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        {{ $syncProcessed }}/{{ $syncTotal }}&nbsp;({{ $pct }}%)
                    @else
                        Sync with sources
                    @endif
                </span>
            </button>

            @if($syncStatus === 'running' || $syncStatus === 'done' || $syncStatus === 'error')
                <div class="w-full rounded-lg border px-4 py-3 text-sm
                    {{ $syncStatus === 'done'    ? 'bg-green-50 border-green-200 text-green-800' : '' }}
                    {{ $syncStatus === 'error'   ? 'bg-red-50 border-red-200 text-red-700'       : '' }}
                    {{ $syncStatus === 'running' ? 'bg-blue-50 border-blue-200 text-blue-800'    : '' }}
                ">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium">
                            @if($syncStatus === 'running') Syncing compounds…
                            @elseif($syncStatus === 'done') Sync complete
                            @else Sync error
                            @endif
                        </span>
                        <span class="tabular-nums text-xs">
                            {{ $syncProcessed }}/{{ $syncTotal }}
                            @if($syncEnriched > 0) &nbsp;·&nbsp;{{ $syncEnriched }} enriched @endif
                            @if($syncFailed  > 0) &nbsp;·&nbsp;<span class="text-red-600">{{ $syncFailed }} failed</span> @endif
                        </span>
                    </div>
                    @php $pct = $syncTotal > 0 ? round($syncProcessed / $syncTotal * 100) : 0; @endphp
                    <div class="w-full rounded-full h-2 bg-current opacity-20 mb-1">
                        <div class="rounded-full h-2
                            {{ $syncStatus === 'done'    ? 'bg-green-600' : '' }}
                            {{ $syncStatus === 'error'   ? 'bg-red-500'   : '' }}
                            {{ $syncStatus === 'running' ? 'bg-blue-600'  : '' }}
                        " style="width: {{ $pct }}%; opacity: 1;"></div>
                    </div>
                    @if($syncCurrent && $syncStatus === 'running')
                        <p class="mt-1 text-xs opacity-70 truncate">{{ $syncCurrent }}</p>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4">
        <div class="w-full flex flex-row gap-4 md:items-center md:justify-between mb-6">
            <div class="w-1/3">
                <h1 class="text-2xl font-semibold">Compounds</h1>
                <p class="text-sm text-gray-600">Browse and search all compounds in the database.</p>
            </div>

            <div class="w-2/3 flex items-center gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search by name, synonym, InChIKey, HMDB, ChEBI..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                >

                <select
                    wire:model.live="perPage"
                    class="w-1/4 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                >
                    <option value="10">10 / page</option>
                    <option value="15">15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>

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

                {{-- Columns picker --}}
                <div class="relative" @click.stop>
                    <button
                        type="button"
                        @click="colsOpen = !colsOpen"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 focus:outline-none"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        Columns
                        <svg class="w-3 h-3 text-gray-400 transition-transform" :class="colsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="colsOpen"
                        @click.outside="colsOpen = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 top-full mt-1 z-30 w-52 rounded-xl border border-gray-200 bg-white shadow-lg py-1 max-h-72 overflow-y-auto"
                        style="display:none"
                    >
                        <template x-for="col in colDefs" :key="col.key">
                            <label class="flex items-center gap-2.5 px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm select-none">
                                <input
                                    type="checkbox"
                                    :checked="cols[col.key]"
                                    @change="cols[col.key] = $event.target.checked; saveCol()"
                                    class="rounded border-gray-300 text-black focus:ring-black"
                                >
                                <span x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Filter panel ── --}}
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
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="filterHasPubchem" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                    Has PubChem CID
                </label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="filterHasHmdb" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                    Has HMDB
                </label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="filterHasCas" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                    Has CAS
                </label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="filterHasSmiles" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                    Has SMILES
                </label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="filterIsTerpene" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                    Is Terpene
                </label>

                <div class="flex items-end gap-2 ml-auto">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Kingdom</p>
                        <select wire:model.live="filterKingdom" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200">
                            <option value="">All kingdoms</option>
                            @foreach($kingdoms as $kingdom)
                                <option value="{{ $kingdom }}">{{ $kingdom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Class</p>
                        <select wire:model.live="filterClass" class="text-sm rounded-lg border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring focus:ring-blue-200 disabled:opacity-40 disabled:cursor-not-allowed" @disabled($filterKingdom === '')>
                            <option value="">All classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}">{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($activeFilterCount > 0)
                        <button wire:click="clearFilters" class="flex items-center gap-1 text-sm text-red-500 hover:text-red-700 px-2 py-1.5 rounded-lg hover:bg-red-50">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear ({{ $activeFilterCount }})
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="w-full bg-white shadow rounded-xl overflow-hidden">
            @include('livewire.compounds.components.compounds-table')
        </div>
    </div>

    @include('livewire.compounds.components.compound-modal')
</div>
