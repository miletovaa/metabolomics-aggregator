<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr class="text-left text-gray-700">
                <th class="px-4 py-3 cursor-pointer whitespace-nowrap" x-show="cols.id" wire:click="sortBy('id')">
                    ID @if($sortField==='id')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@else<span class="text-gray-400">↕</span>@endif
                </th>
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
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.ri_polar">RI (polar)</th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_kingdom">Kingdom</th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_superclass">Superclass</th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_class">Class</th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_subclass">Subclass</th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.taxonomy_direct_parent">Direct Parent</th>
                <th class="px-4 py-3 cursor-pointer whitespace-nowrap" x-show="cols.projects" wire:click="sortBy('projects_count')">
                    Projects @if($sortField==='projects_count')<span>{{ $sortDirection==='asc'?'↑':'↓' }}</span>@else<span class="text-gray-400">↕</span>@endif
                </th>
                <th class="px-4 py-3 whitespace-nowrap" x-show="cols.description">Comment</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            @forelse($compounds as $compound)
                <tr
                    class="hover:bg-gray-50 cursor-pointer"
                    wire:click="openCompoundModal({{ $compound->id }})"
                >
                    <td class="px-4 py-3 text-gray-700" x-show="cols.id">{{ $compound->id }}</td>

                    <td class="px-4 py-3 font-medium text-gray-900" x-show="cols.canonical_name">
                        {{ $compound->canonical_name }}
                    </td>

                    <td class="px-4 py-3 text-gray-700 max-w-xs" x-show="cols.iupac_name">
                        <div class="truncate" title="{{ $compound->iupac_name }}">{{ $compound->iupac_name ?: '—' }}</div>
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.molecular_formula">
                        {{ $compound->molecular_formula ?: '—' }}
                    </td>

                    <td class="px-4 py-3 font-mono text-xs max-w-xs" x-show="cols.smiles">
                        <div class="truncate" title="{{ $compound->smiles }}">{{ $compound->smiles ?: '—' }}</div>
                    </td>

                    <td class="px-4 py-3 font-mono text-xs max-w-xs" x-show="cols.inchi">
                        <div class="truncate" title="{{ $compound->inchi }}">{{ $compound->inchi ?: '—' }}</div>
                    </td>

                    <td class="px-4 py-3 text-gray-700 font-mono text-xs" x-show="cols.inchikey">
                        {{ $compound->inchikey ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.pubchem_cid">
                        {{ $compound->pubchem_cid ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.cas">
                        {{ $compound->cas ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.hmdb_id">
                        {{ $compound->hmdb_id ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.chebi_id">
                        {{ $compound->chebi_id ?: '—' }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap" x-show="cols.ri_polar">
                        @php
                            $polarRis = $compound->retentionIndices->where('is_polar', true)->sortBy('value');
                        @endphp
                        @if($polarRis->isEmpty())
                            <span class="text-gray-400">—</span>
                        @else
                            <span class="text-xs text-gray-700">
                                @foreach($polarRis->take(2) as $ri)
                                    <span class="inline-flex items-center gap-0.5">
                                        {{ number_format((float)$ri->value, 1) }}
                                        <span class="text-gray-400">({{ $ri->source?->name ?? '?' }})</span>
                                    </span>
                                    @if(!$loop->last) · @endif
                                @endforeach
                                @if($polarRis->count() > 2)
                                    <span class="text-gray-400">+{{ $polarRis->count() - 2 }}</span>
                                @endif
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.taxonomy_kingdom">
                        {{ $compound->taxonomy?->kingdom ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.taxonomy_superclass">
                        {{ $compound->taxonomy?->superclass ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.taxonomy_class">
                        {{ $compound->taxonomy?->{'class'} ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.taxonomy_subclass">
                        {{ $compound->taxonomy?->subclass ?: '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-700" x-show="cols.taxonomy_direct_parent">
                        {{ $compound->taxonomy?->direct_parent ?: '—' }}
                    </td>

                    <td class="px-4 py-3" x-show="cols.projects" @click.stop>
                        @php $uniqueProjects = $compound->projects->unique('id'); @endphp
                        @if($uniqueProjects->isEmpty())
                            <span class="text-gray-400">—</span>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($uniqueProjects->take(3) as $project)
                                    <span class="inline-block rounded-full bg-blue-100 text-blue-700 text-xs px-2 py-0.5 whitespace-nowrap">{{ $project->name }}</span>
                                @endforeach
                                @if($uniqueProjects->count() > 3)
                                    <span class="inline-block rounded-full bg-gray-100 text-gray-500 text-xs px-2 py-0.5">+{{ $uniqueProjects->count() - 3 }}</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-3 max-w-xs" x-show="cols.description">
                        <div
                            x-data="{
                                editing: false,
                                value: @js($compound->description ?? ''),
                                original: @js($compound->description ?? ''),
                                cancelled: false,
                                startEdit() {
                                    this.editing = true;
                                    this.$nextTick(() => this.$refs.descInput.focus());
                                },
                                cancel() {
                                    this.cancelled = true;
                                    this.value = this.original;
                                    this.editing = false;
                                },
                                save() {
                                    if (!this.cancelled && this.value !== this.original) {
                                        $wire.updateDescription({{ $compound->id }}, this.value);
                                        this.original = this.value;
                                    }
                                    this.cancelled = false;
                                    this.editing = false;
                                }
                            }"
                            class="flex items-start gap-1.5 min-w-0 group/desc"
                        >
                            <span
                                x-show="!editing"
                                @dblclick.stop="startEdit()"
                                class="cursor-default truncate text-gray-600"
                                title="Double-click to edit"
                                x-text="value || '—'"
                            ></span>
                            <textarea
                                x-show="editing"
                                x-ref="descInput"
                                x-model="value"
                                rows="2"
                                @click.stop
                                @keydown.escape.prevent="cancel()"
                                @blur="save()"
                                class="border border-gray-300 rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-black resize-none"
                            ></textarea>
                            <button
                                x-show="!editing"
                                @click.stop="startEdit()"
                                class="shrink-0 mt-0.5 opacity-0 group-hover/desc:opacity-40 hover:!opacity-100 transition-opacity"
                                title="Edit comment"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="19" class="px-4 py-8 text-center text-gray-500">No compounds found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
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
                        wire:click="goToPageNumber({{ $pageNumber }})"
                        class="rounded-lg border px-3 py-1.5 {{ $page === $pageNumber ? 'bg-gray-900 text-white' : 'hover:bg-white' }}"
                    >
                        {{ $pageNumber }}
                    </button>
                @endif
            @endforeach

            <button
                type="button"
                wire:click="nextCompoundsPage"
                @disabled($page >= $compounds->lastPage())
                class="rounded-lg border px-3 py-1.5 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
            >
                Next
            </button>
        </div>
    </div>
</div>
