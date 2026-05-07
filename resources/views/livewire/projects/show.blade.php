<div
    class="max-w-7xl mx-auto px-4 py-6"
    x-data="{
        colsOpen: false,
        filtersOpen: {{ $activeFilterCount > 0 ? 'true' : 'false' }},
        cols: {},
        colDefs: [
            { key: 'input_name',            label: 'Input Name' },
            { key: 'custom_name',           label: 'Custom Name' },
            { key: 'ri',                    label: 'RI' },
            { key: 'ri_lit',                label: 'RI (db)' },
            { key: 'mz',                    label: 'MZ' },
            { key: 'is_mapped',             label: 'Is Mapped' },
            { key: 'is_duplicate',          label: 'Is Duplicate' },
            { key: 'is_terpene',            label: 'Is Terpene' },
            { key: 'terpene_type',          label: 'Terpene Type' },
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
            { key: 'taxonomy_kingdom',      label: 'Kingdom' },
            { key: 'taxonomy_superclass',   label: 'Superclass' },
            { key: 'taxonomy_class',        label: 'Class' },
            { key: 'taxonomy_subclass',     label: 'Subclass' },
            { key: 'taxonomy_direct_parent',label: 'Direct Parent' },
        ],
        init() {
            const defaults = { input_name: true, custom_name: true, ri: true, ri_lit: true, mz: true, is_mapped: true, is_duplicate: false, is_terpene: false, terpene_type: false, canonical_name: true, iupac_name: true, molecular_formula: true, smiles: false, inchi: false, inchikey: true, pubchem_cid: false, cas: true, hmdb_id: false, chebi_id: false, taxonomy_kingdom: false, taxonomy_superclass: false, taxonomy_class: false, taxonomy_subclass: false, taxonomy_direct_parent: false };
            const saved = JSON.parse(localStorage.getItem('pc_cols') || 'null');
            this.cols = { ...defaults, ...(saved || {}) };
        },
        saveCol(key) {
            localStorage.setItem('pc_cols', JSON.stringify(this.cols));
        }
    }"
    @click.outside="colsOpen = false"
>

    {{-- ── Notification banner ── --}}
    @if($successMessage || $errorMessage)
        <div
            x-data="{ visible: true }"
            x-init="setTimeout(() => { visible = false; $wire.dismissNotification() }, 5000)"
            x-show="visible"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="mb-4 flex items-center justify-between rounded-lg px-4 py-3 text-sm font-medium shadow
                   {{ $successMessage ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}"
        >
            <div class="flex items-center gap-2">
                @if($successMessage)
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $successMessage }}
                @else
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14A7 7 0 0012 5z"/></svg>
                    {{ $errorMessage }}
                @endif
            </div>
            <button @click="visible = false; $wire.dismissNotification()" class="ml-4 opacity-60 hover:opacity-100">✕</button>
        </div>
    @endif

    <div>
        <div class="flex justify-end mb-4">
            <div class="flex gap-2">

                <button
                    type="button"
                    wire:click="openImportModal"
                    class="bg-green-500 text-white px-3 py-1 rounded"
                >
                    + Add/import compounds
                </button>

                <button
                    wire:click="mapLocal"
                    wire:loading.attr="disabled"
                    wire:target="mapLocal"
                    class="relative bg-blue-500 text-white px-3 py-1 rounded disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="mapLocal">Map with local database</span>
                    <span wire:loading wire:target="mapLocal" class="flex items-center gap-1">
                        <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Mapping…
                    </span>
                </button>
                
                <button
                    type="button"
                    wire:click="openExportModal"
                    class="bg-gray-700 text-white px-3 py-1 rounded flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </button>

            </div>
        </div>
    </div>

    {{-- ── Search / per-page / columns ── --}}
    <div class="w-full flex flex-row gap-4 mb-4">
        <div class="w-1/3">
            <h1 class="text-2xl font-semibold">{{ $project->name }}</h1>
            <p class="text-sm text-gray-600">Browse and search compounds of the project.</p>
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
                                @change="cols[col.key] = $event.target.checked; saveCol(col.key)"
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
                <input type="checkbox" wire:model.live="filterIsMapped" class="rounded border-gray-300 text-blue-500 focus:ring-blue-400">
                Is Mapped
            </label>
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

    {{-- ── Table ── --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm bg-white shadow rounded">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">Actions</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.input_name">Input Name</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.custom_name">Custom Name</th>
                    <th class="px-4 py-3" x-show="cols.ri">RI</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.ri_lit">RI (db)</th>
                    <th class="px-4 py-3" x-show="cols.mz">MZ</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.is_mapped">Is Mapped</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.is_duplicate">Is Duplicate</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.is_terpene">Is Terpene</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.terpene_type">Terpene Type</th>
                    <th class="px-4 py-3 cursor-pointer whitespace-nowrap" x-show="cols.canonical_name" wire:click="sortBy('canonical_name')">
                        Canonical Name @if($sortField==='canonical_name')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@else<span class="text-gray-400">↕</span>@endif
                    </th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.iupac_name">IUPAC Name</th>
                    <th class="px-4 py-3 cursor-pointer whitespace-nowrap" x-show="cols.molecular_formula" wire:click="sortBy('molecular_formula')">
                        Formula @if($sortField==='molecular_formula')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@else<span class="text-gray-400">↕</span>@endif
                    </th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.smiles">SMILES</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.inchi">InChI</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.inchikey">InChIKey</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.pubchem_cid">PubChem CID</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.cas">CAS</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.hmdb_id">HMDB</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.chebi_id">ChEBI</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_kingdom">Kingdom</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_superclass">Superclass</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_class">Class</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_subclass">Subclass</th>
                    <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_direct_parent">Direct Parent</th>
                </tr>
            </thead>

            <tbody>
                @forelse($compounds as $c)
                    <tr
                        wire:key="pc-{{ $c->id }}"
                        wire:click="openProjectCompoundModal({{ $c->id }})"
                        class="border-t group cursor-pointer {{ $c->is_duplicate ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-gray-50' }}"
                    >

                        {{-- Actions --}}
                        <td class="px-4 py-3 whitespace-nowrap" @click.stop>
                            @if(!$c->is_mapped)
                                <button
                                    wire:click="mapSingle({{ $c->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="mapSingle({{ $c->id }})"
                                    class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 disabled:opacity-50 flex items-center gap-1"
                                    title="Map this compound"
                                >
                                    <span wire:loading.remove wire:target="mapSingle({{ $c->id }})">Map</span>
                                    <span wire:loading wire:target="mapSingle({{ $c->id }})" class="flex items-center gap-1">
                                        <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                        …
                                    </span>
                                </button>
                            @else
                                <span class="text-xs text-green-600 font-medium">✓ Mapped</span>
                            @endif
                        </td>

                        {{-- Input Name (read-only) --}}
                        <td class="px-4 py-3 text-gray-500" x-show="cols.input_name">
                            {{ $c->input_name ?? $c->custom_name }}
                        </td>

                        {{-- Custom Name (double-click to edit) --}}
                        <td class="px-4 py-3" x-show="cols.custom_name">
                            <div
                                x-data="{
                                    editing: false,
                                    name: @js($c->custom_name ?? ''),
                                    original: @js($c->custom_name ?? ''),
                                    cancelled: false,
                                    startEdit() {
                                        this.editing = true;
                                        this.$nextTick(() => this.$refs.cnInput.select());
                                    },
                                    cancel() {
                                        this.cancelled = true;
                                        this.name = this.original;
                                        this.editing = false;
                                    },
                                    save() {
                                        if (!this.cancelled && this.name.trim() !== '' && this.name !== this.original) {
                                            $wire.updateCustomName({{ $c->id }}, this.name);
                                            this.original = this.name;
                                        }
                                        this.cancelled = false;
                                        this.editing = false;
                                    }
                                }"
                                class="flex items-center gap-1.5 min-w-0"
                                @click.stop
                            >
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="cursor-default truncate"
                                    title="Double-click to edit"
                                    x-text="name"
                                ></span>

                                <input
                                    x-show="editing"
                                    x-ref="cnInput"
                                    x-model="name"
                                    @click.stop
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-full focus:outline-none focus:ring-2 focus:ring-black"
                                />

                                <button
                                    x-show="!editing"
                                    @click.stop="startEdit()"
                                    class="shrink-0 opacity-0 group-hover:opacity-40 hover:!opacity-100 transition-opacity"
                                    title="Edit custom name"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>

                        {{-- RI (double-click to edit) --}}
                        <td class="px-4 py-3" x-show="cols.ri">
                            <div
                                x-data="{
                                    editing: false,
                                    value: @js($c->rt !== null ? number_format((float)$c->rt, 2, '.', '') : ''),
                                    original: @js($c->rt !== null ? number_format((float)$c->rt, 2, '.', '') : ''),
                                    cancelled: false,
                                    startEdit() {
                                        this.editing = true;
                                        this.$nextTick(() => this.$refs.riInput.select());
                                    },
                                    cancel() {
                                        this.cancelled = true;
                                        this.value = this.original;
                                        this.editing = false;
                                    },
                                    save() {
                                        if (!this.cancelled && this.value !== this.original) {
                                            $wire.updateRt({{ $c->id }}, this.value);
                                            this.original = this.value;
                                        }
                                        this.cancelled = false;
                                        this.editing = false;
                                    }
                                }"
                                class="flex items-center gap-1.5 min-w-0"
                                @click.stop
                            >
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="cursor-default"
                                    title="Double-click to edit"
                                    x-text="value || '—'"
                                ></span>
                                <input
                                    x-show="editing"
                                    x-ref="riInput"
                                    x-model="value"
                                    type="text"
                                    inputmode="decimal"
                                    @click.stop
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-24 focus:outline-none focus:ring-2 focus:ring-black"
                                />
                                <button
                                    x-show="!editing"
                                    @click.stop="startEdit()"
                                    class="shrink-0 opacity-0 group-hover:opacity-40 hover:!opacity-100 transition-opacity"
                                    title="Edit RI"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                </button>
                            </div>
                        </td>

                        {{-- RI literature (from HMDB/NIST via mapped compound) --}}
                        <td class="px-4 py-3 whitespace-nowrap" x-show="cols.ri_lit">
                            @php
                                $litRis = $c->compound?->retentionIndices?->where('is_polar', true)->sortBy('value') ?? collect();
                            @endphp
                            @if($litRis->isEmpty())
                                <span class="text-gray-400">—</span>
                            @else
                                <span class="text-xs text-gray-700">
                                    @foreach($litRis->take(2) as $ri)
                                        <span class="inline-flex items-center gap-0.5">
                                            {{ number_format((float)$ri->value, 1) }}
                                            <span class="text-gray-400">({{ $ri->source?->name ?? '?' }})</span>
                                        </span>
                                        @if(!$loop->last) · @endif
                                    @endforeach
                                    @if($litRis->count() > 2)
                                        <span class="text-gray-400">+{{ $litRis->count() - 2 }}</span>
                                    @endif
                                </span>
                            @endif
                        </td>

                        {{-- MZ (double-click to edit) --}}
                        <td class="px-4 py-3" x-show="cols.mz">
                            <div
                                x-data="{
                                    editing: false,
                                    value: @js($c->mz !== null ? number_format((float)$c->mz, 2, '.', '') : ''),
                                    original: @js($c->mz !== null ? number_format((float)$c->mz, 2, '.', '') : ''),
                                    cancelled: false,
                                    startEdit() {
                                        this.editing = true;
                                        this.$nextTick(() => this.$refs.mzInput.select());
                                    },
                                    cancel() {
                                        this.cancelled = true;
                                        this.value = this.original;
                                        this.editing = false;
                                    },
                                    save() {
                                        if (!this.cancelled && this.value !== this.original) {
                                            $wire.updateMz({{ $c->id }}, this.value);
                                            this.original = this.value;
                                        }
                                        this.cancelled = false;
                                        this.editing = false;
                                    }
                                }"
                                class="flex items-center gap-1.5 min-w-0"
                                @click.stop
                            >
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="cursor-default"
                                    title="Double-click to edit"
                                    x-text="value || '—'"
                                ></span>
                                <input
                                    x-show="editing"
                                    x-ref="mzInput"
                                    x-model="value"
                                    type="text"
                                    inputmode="decimal"
                                    @click.stop
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-24 focus:outline-none focus:ring-2 focus:ring-black"
                                />
                                <button
                                    x-show="!editing"
                                    @click.stop="startEdit()"
                                    class="shrink-0 opacity-0 group-hover:opacity-40 hover:!opacity-100 transition-opacity"
                                    title="Edit m/z"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3" x-show="cols.is_mapped">{{ $c->is_mapped ? '✓' : '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.is_duplicate">
                            @if($c->is_duplicate)
                                @php
                                    $others = collect($duplicateGroups[$c->compound_id] ?? [])
                                        ->filter(fn($n) => $n !== $c->custom_name)
                                        ->values();
                                @endphp
                                <span
                                    x-data="{ open: false }"
                                    @mouseenter="open = true"
                                    @mouseleave="open = false"
                                    class="relative inline-block"
                                >
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 text-xs px-2 py-0.5 cursor-default font-medium">
                                        Duplicate
                                    </span>
                                    @if($others->isNotEmpty())
                                        <div
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-1 w-52 rounded-lg bg-gray-900 text-white text-xs p-2 shadow-lg whitespace-normal pointer-events-none"
                                            style="display: none"
                                        >
                                            <p class="font-medium mb-1 opacity-75">Same compound as:</p>
                                            @foreach($others as $otherName)
                                                <div class="truncate">• {{ $otherName }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3" x-show="cols.is_terpene">
                            @if($c->is_terpene)
                                <span class="inline-flex items-center rounded-full bg-green-100 text-green-700 text-xs px-2 py-0.5 font-medium">Terpene</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap" x-show="cols.terpene_type">
                            @if($c->terpene_type)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 font-medium">{{ $c->terpene_type }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3" x-show="cols.canonical_name">{{ $c->compound->canonical_name ?? '—' }}</td>
                        <td class="px-4 py-3 max-w-xs" x-show="cols.iupac_name"><div class="truncate" title="{{ $c->compound->iupac_name ?? '' }}">{{ $c->compound->iupac_name ?? '—' }}</div></td>
                        <td class="px-4 py-3" x-show="cols.molecular_formula">{{ $c->compound->molecular_formula ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs max-w-xs" x-show="cols.smiles"><div class="truncate" title="{{ $c->compound->smiles ?? '' }}">{{ $c->compound->smiles ?? '—' }}</div></td>
                        <td class="px-4 py-3 font-mono text-xs max-w-xs" x-show="cols.inchi"><div class="truncate" title="{{ $c->compound->inchi ?? '' }}">{{ $c->compound->inchi ?? '—' }}</div></td>
                        <td class="px-4 py-3 font-mono text-xs" x-show="cols.inchikey">{{ $c->compound->inchikey ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.pubchem_cid">{{ $c->compound->pubchem_cid ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.cas">{{ $c->compound->cas ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.hmdb_id">{{ $c->compound->hmdb_id ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.chebi_id">{{ $c->compound->chebi_id ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.taxonomy_kingdom">{{ $c->compound->taxonomy?->kingdom ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.taxonomy_superclass">{{ $c->compound->taxonomy?->superclass ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.taxonomy_class">{{ $c->compound->taxonomy?->{'class'} ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.taxonomy_subclass">{{ $c->compound->taxonomy?->subclass ?? '—' }}</td>
                        <td class="px-4 py-3" x-show="cols.taxonomy_direct_parent">{{ $c->compound->taxonomy?->direct_parent ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="25" class="px-4 py-8 text-center text-gray-400">No compounds found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Pagination ── --}}
    <div class="px-4 py-4 border-t bg-gray-50">
        <div class="flex items-center justify-between gap-4 px-4 py-4 bg-gray-50 text-sm text-gray-700">
            <div>
                Showing {{ $compounds->firstItem() ?? 0 }}–{{ $compounds->lastItem() ?? 0 }} of {{ $compounds->total() }} compounds
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="previousCompoundsPage"
                    @disabled($page <= 1)
                    class="rounded-lg border px-3 py-1.5 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Previous
                </button>

                @php
                    if ($lastPage <= 6) {
                        $pageNumbers = range(1, $lastPage);
                    } else {
                        $pageNumbers = [];
                        for ($i = 1; $i <= 2; $i++) { $pageNumbers[] = $i; }
                        if ($page >= 1 && $page < 5) {
                            for ($i = 3; $i <= 4; $i++) { $pageNumbers[] = $i; }
                            $pageNumbers[] = '...';
                        } elseif ($page >= 5 && $page < $lastPage - 4) {
                            $pageNumbers[] = '...';
                            for ($i = $page - 1; $i <= $page + 1; $i++) { $pageNumbers[] = $i; }
                            $pageNumbers[] = '...';
                        } else {
                            $pageNumbers[] = '...';
                            for ($i = $lastPage - 4; $i < $lastPage - 2; $i++) { $pageNumbers[] = $i; }
                        }
                        for ($i = $lastPage - 2; $i <= $lastPage; $i++) { $pageNumbers[] = $i; }
                    }
                @endphp

                @foreach($pageNumbers as $pageNumber)
                    @if($pageNumber === '...')
                        <span class="px-3 py-1.5">…</span>
                    @else
                        <button
                            type="button"
                            wire:click="goToCompoundsPage({{ $pageNumber }})"
                            class="rounded-lg border px-3 py-1.5 {{ $page === $pageNumber ? 'bg-gray-900 text-white' : 'hover:bg-white' }}"
                        >
                            {{ $pageNumber }}
                        </button>
                    @endif
                @endforeach

                <button
                    type="button"
                    wire:click="nextCompoundsPage"
                    @disabled($page >= $lastPage)
                    class="rounded-lg border px-3 py-1.5 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </div>

    @include('livewire.projects.components.project-compound-modal')

    {{-- ── Export modal ── --}}
    <div
        x-data
        x-show="$wire.showExportModal"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.closeExportModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        style="display: none"
    >
        <div class="absolute inset-0" wire:click="closeExportModal"></div>

        <div
            class="relative bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl"
            @click.stop
        >
            {{-- Header --}}
            <div class="sticky top-0 z-10 flex items-start justify-between border-b bg-white px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold">Export Compounds</h2>
                    <p class="text-sm text-gray-500">Choose fields and file format for the export.</p>
                </div>
                <button type="button" wire:click="closeExportModal" class="text-gray-400 hover:text-gray-700 text-xl leading-none">✕</button>
            </div>

            <div class="px-6 py-5 space-y-6">

                {{-- Field selection --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Fields to export</h3>
                        <div class="flex gap-3 text-xs">
                            <button wire:click="selectAllExportFields" class="text-blue-600 hover:underline">Select all</button>
                            <button wire:click="clearExportFields" class="text-gray-500 hover:underline">Deselect all</button>
                        </div>
                    </div>

                    @error('exportFields')
                        <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="space-y-4">
                        @foreach($exportFieldGroups as $groupName => $groupFields)
                            <div>
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $groupName }}</p>
                                <div class="grid grid-cols-2 gap-1.5">
                                    @foreach($groupFields as $key => $label)
                                        <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                                            <input
                                                type="checkbox"
                                                wire:model="exportFields"
                                                value="{{ $key }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                            >
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Format --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">File format</h3>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model="exportFormat" value="csv" class="text-blue-600 focus:ring-blue-400">
                            <span>CSV <span class="text-gray-400 text-xs">(.csv)</span></span>
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model="exportFormat" value="txt" class="text-blue-600 focus:ring-blue-400">
                            <span>Tab-separated <span class="text-gray-400 text-xs">(.txt)</span></span>
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model="exportFormat" value="xlsx" class="text-blue-600 focus:ring-blue-400">
                            <span>Excel <span class="text-gray-400 text-xs">(.xlsx)</span></span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 border-t px-6 py-4 bg-gray-50 rounded-b-xl">
                <button
                    type="button"
                    wire:click="closeExportModal"
                    class="rounded-lg border px-4 py-2 text-sm hover:bg-white"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="export"
                    wire:loading.attr="disabled"
                    wire:target="export"
                    class="rounded-lg bg-gray-800 text-white px-4 py-2 text-sm hover:bg-gray-700 disabled:opacity-60 flex items-center gap-2"
                >
                    <svg wire:loading wire:target="export" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg wire:loading.remove wire:target="export" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span wire:loading.remove wire:target="export">Export</span>
                    <span wire:loading wire:target="export">Exporting…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Import / Manual modal ── --}}
    <div
        x-data
        x-show="$wire.showImportModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.closeImportModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        style="display: none"
    >
        <div class="absolute inset-0" wire:click="closeImportModal"></div>

        <div
            class="relative bg-white p-6 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto space-y-6 shadow-xl"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Add compounds</h2>
                    <p class="text-sm text-gray-500">Only name is required. RI and m/z are optional.</p>
                </div>
                <button type="button" wire:click="closeImportModal" class="text-gray-500 hover:text-gray-900">✕</button>
            </div>

            {{-- CSV / XLSX import --}}
            <div class="border rounded-lg p-4 space-y-3">
                <h3 class="font-medium">Import CSV / XLSX</h3>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls" class="w-full text-sm">
                <div wire:loading wire:target="file" class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    Uploading file…
                </div>
                <button
                    type="button"
                    wire:click="importIntoProject"
                    wire:loading.attr="disabled"
                    wire:target="importIntoProject,file"
                    class="bg-black text-white px-4 py-2 rounded disabled:opacity-50 flex items-center gap-2"
                >
                    <svg wire:loading wire:target="importIntoProject" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    <span wire:loading.remove wire:target="importIntoProject">Import</span>
                    <span wire:loading wire:target="importIntoProject">Importing…</span>
                </button>
                @error('file') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500">
                    With headers, use columns like <code>name</code>, <code>compound</code>, <code>ri</code>/<code>rt</code>, and <code>mz</code>/<code>m/z</code>.
                    Without headers the first three columns are read as name, RI, and m/z.
                </p>
            </div>

            {{-- Manual entry --}}
            <div class="border rounded-lg p-4 space-y-3">
                <div
                    x-data="{
                        rows: [{ name: '', ri: '', mz: '' }],
                        addRow()  { this.rows.push({ name: '', ri: '', mz: '' }) },
                        removeRow(i) { this.rows.splice(i, 1) },
                        save() { $wire.saveManualCompounds(this.rows) }
                    }"
                    x-init="$watch('$wire.showImportModal', val => {
                        if (val) rows = [{ name: '', ri: '', mz: '' }]
                    })"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="font-medium">Manual entry</h3>
                            <p class="text-xs text-gray-500">Add one or more compounds by hand.</p>
                        </div>
                        <button type="button" @click="addRow()" class="text-sm text-blue-600 hover:underline">+ Add row</button>
                    </div>

                    <div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2 text-xs font-medium text-gray-500 px-1 mb-1">
                        <span>Name <span class="text-red-400">*</span></span>
                        <span>RI (optional)</span>
                        <span>m/z (optional)</span>
                        <span></span>
                    </div>

                    <div wire:ignore>
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2 items-center mb-2">
                                <input x-model="row.name" placeholder="e.g. Caffeine" class="w-full border px-3 py-2 rounded text-sm">
                                <input x-model="row.ri"   placeholder="e.g. 1234"    class="border px-3 py-2 rounded text-sm">
                                <input x-model="row.mz"   placeholder="e.g. 194.08"  class="border px-3 py-2 rounded text-sm">
                                <button type="button" @click="removeRow(i)" class="text-gray-400 hover:text-red-500 text-xl leading-none px-1" title="Remove row">&times;</button>
                            </div>
                        </template>
                    </div>

                    @error('manualCompounds') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <button
                        type="button"
                        @click="save()"
                        wire:loading.attr="disabled"
                        wire:target="saveManualCompounds"
                        class="mt-1 bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg wire:loading wire:target="saveManualCompounds" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <span wire:loading.remove wire:target="saveManualCompounds">Save compound(s)</span>
                        <span wire:loading wire:target="saveManualCompounds">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
