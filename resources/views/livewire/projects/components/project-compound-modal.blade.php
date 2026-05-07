@if($showProjectCompoundModal && $selectedProjectCompound)
    @php $pc = $selectedProjectCompound; $compound = $pc->compound; @endphp
    <div
        x-data="{ open: true }"
        x-show="open"
        @keydown.escape.window="open = false; $wire.closeProjectCompoundModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        wire:key="pc-modal-{{ $pc->id }}"
    >
        <div
            class="absolute inset-0"
            @click="open = false; $wire.closeProjectCompoundModal()"
        ></div>

        <div
            class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl"
            @click.stop
            x-show="open"
        >
            {{-- ── Header ── --}}
            <div class="sticky top-0 z-10 flex items-start justify-between border-b bg-white px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ $compound?->canonical_name ?? $pc->custom_name }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Project entry #{{ $pc->id }}
                        @if($compound) · Compound ID {{ $compound->id }} @endif
                    </p>
                </div>
                <button
                    type="button"
                    @click="open = false; $wire.closeProjectCompoundModal()"
                    class="rounded-lg px-3 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                >
                    ✕
                </button>
            </div>

            <div class="space-y-6 px-6 py-5">

                {{-- ── Project compound fields ── --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Project entry
                    </h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

                        {{-- Input Name --}}
                        <div class="rounded-lg border bg-gray-50 p-3">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Input Name</div>
                            <div class="text-sm text-gray-900">{{ $pc->input_name ?? '—' }}</div>
                        </div>

                        {{-- Custom Name (editable) --}}
                        <div class="rounded-lg border bg-gray-50 p-3"
                             x-data="{
                                editing: false,
                                name: @js($pc->custom_name ?? ''),
                                original: @js($pc->custom_name ?? ''),
                                cancelled: false,
                                startEdit() { this.editing = true; this.$nextTick(() => this.$refs.cnInput.select()); },
                                cancel() { this.cancelled = true; this.name = this.original; this.editing = false; },
                                save() {
                                    if (!this.cancelled && this.name.trim() !== '' && this.name !== this.original) {
                                        $wire.updateCustomName({{ $pc->id }}, this.name);
                                        this.original = this.name;
                                    }
                                    this.cancelled = false; this.editing = false;
                                }
                             }"
                        >
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Custom Name <span class="normal-case text-gray-400 font-normal">(dbl-click to edit)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="text-sm text-gray-900 cursor-default"
                                    x-text="name || '—'"
                                ></span>
                                <input
                                    x-show="editing"
                                    x-ref="cnInput"
                                    x-model="name"
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-full focus:outline-none focus:ring-2 focus:ring-black"
                                />
                                <button
                                    x-show="!editing"
                                    @click="startEdit()"
                                    class="shrink-0 text-gray-400 hover:text-gray-700"
                                    title="Edit"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- RI (editable) --}}
                        <div class="rounded-lg border bg-gray-50 p-3"
                             x-data="{
                                editing: false,
                                value: @js($pc->rt !== null ? number_format((float)$pc->rt, 2, '.', '') : ''),
                                original: @js($pc->rt !== null ? number_format((float)$pc->rt, 2, '.', '') : ''),
                                cancelled: false,
                                startEdit() { this.editing = true; this.$nextTick(() => this.$refs.riInput.select()); },
                                cancel() { this.cancelled = true; this.value = this.original; this.editing = false; },
                                save() {
                                    if (!this.cancelled && this.value !== this.original) {
                                        $wire.updateRt({{ $pc->id }}, this.value);
                                        this.original = this.value;
                                    }
                                    this.cancelled = false; this.editing = false;
                                }
                             }"
                        >
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                                RI <span class="normal-case text-gray-400 font-normal">(dbl-click to edit)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="text-sm text-gray-900 cursor-default"
                                    x-text="value || '—'"
                                ></span>
                                <input
                                    x-show="editing"
                                    x-ref="riInput"
                                    x-model="value"
                                    type="text"
                                    inputmode="decimal"
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-black"
                                />
                                <button
                                    x-show="!editing"
                                    @click="startEdit()"
                                    class="shrink-0 text-gray-400 hover:text-gray-700"
                                    title="Edit"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- m/z (editable) --}}
                        <div class="rounded-lg border bg-gray-50 p-3"
                             x-data="{
                                editing: false,
                                value: @js($pc->mz !== null ? number_format((float)$pc->mz, 4, '.', '') : ''),
                                original: @js($pc->mz !== null ? number_format((float)$pc->mz, 4, '.', '') : ''),
                                cancelled: false,
                                startEdit() { this.editing = true; this.$nextTick(() => this.$refs.mzInput.select()); },
                                cancel() { this.cancelled = true; this.value = this.original; this.editing = false; },
                                save() {
                                    if (!this.cancelled && this.value !== this.original) {
                                        $wire.updateMz({{ $pc->id }}, this.value);
                                        this.original = this.value;
                                    }
                                    this.cancelled = false; this.editing = false;
                                }
                             }"
                        >
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
                                m/z <span class="normal-case text-gray-400 font-normal">(dbl-click to edit)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    x-show="!editing"
                                    @dblclick="startEdit()"
                                    class="text-sm text-gray-900 cursor-default"
                                    x-text="value || '—'"
                                ></span>
                                <input
                                    x-show="editing"
                                    x-ref="mzInput"
                                    x-model="value"
                                    type="text"
                                    inputmode="decimal"
                                    @keydown.enter.prevent="save()"
                                    @keydown.escape.prevent="cancel()"
                                    @blur="save()"
                                    class="border border-gray-300 rounded px-2 py-0.5 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-black"
                                />
                                <button
                                    x-show="!editing"
                                    @click="startEdit()"
                                    class="shrink-0 text-gray-400 hover:text-gray-700"
                                    title="Edit"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Is Mapped --}}
                        <div class="rounded-lg border bg-gray-50 p-3">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Is Mapped</div>
                            @if($pc->is_mapped)
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 text-xs px-2 py-0.5 font-medium">
                                    ✓ Mapped
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 text-gray-500 text-xs px-2 py-0.5">
                                    Not mapped
                                </span>
                            @endif
                        </div>

                        {{-- Is Duplicate --}}
                        <div class="rounded-lg border bg-gray-50 p-3">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Is Duplicate</div>
                            @if($pc->is_duplicate)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 text-xs px-2 py-0.5 font-medium">
                                    Duplicate
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </div>

                    </div>
                </section>

                @if($compound)

                    {{-- ── Core information ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Core information</h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <x-compound-info-row label="Canonical name"    :value="$compound->canonical_name" />
                            <x-compound-info-row label="IUPAC name"        :value="$compound->iupac_name" />
                            <x-compound-info-row label="Molecular formula" :value="$compound->molecular_formula" />
                            <x-compound-info-row label="SMILES"            :value="$compound->smiles" mono />
                            <x-compound-info-row label="InChI"             :value="$compound->inchi" mono />
                            <x-compound-info-row label="InChIKey"          :value="$compound->inchikey" mono />
                            <x-compound-info-row label="PubChem CID"       :value="$compound->pubchem_cid" />
                            <x-compound-info-row label="CAS"               :value="$compound->cas" />
                            <x-compound-info-row label="HMDB ID"           :value="$compound->hmdb_id" />
                            <x-compound-info-row label="ChEBI ID"          :value="$compound->chebi_id" />
                            @if($compound->description)
                                <div class="rounded-lg border bg-gray-50 p-3 md:col-span-2">
                                    <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Description</div>
                                    <div class="text-sm text-gray-900 leading-relaxed">{{ $compound->description }}</div>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- ── Synonyms ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Synonyms</h3>
                        @if($compound->synonyms->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($compound->synonyms as $synonym)
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                                        {{ $synonym->name }}
                                        @if($synonym->source)
                                            <span class="text-gray-400">({{ $synonym->source->name }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No synonyms stored.</p>
                        @endif
                    </section>

                    {{-- ── Taxonomy ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Taxonomy</h3>
                        @if($compound->taxonomy)
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <x-compound-info-row label="Kingdom"            :value="$compound->taxonomy->kingdom" />
                                <x-compound-info-row label="Superclass"         :value="$compound->taxonomy->superclass" />
                                <x-compound-info-row label="Class"              :value="$compound->taxonomy->class" />
                                <x-compound-info-row label="Subclass"           :value="$compound->taxonomy->subclass" />
                                <x-compound-info-row label="Direct parent"      :value="$compound->taxonomy->direct_parent" />
                                <x-compound-info-row label="Alternative parents" :value="$compound->taxonomy->alternative_parents" />
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No taxonomy stored.</p>
                        @endif
                    </section>

                    {{-- ── Retention indices ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Retention indices (databases)</h3>
                        @if($compound->retentionIndices->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-left text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2">Value</th>
                                            <th class="px-3 py-2">Column type</th>
                                            <th class="px-3 py-2">Polarity</th>
                                            <th class="px-3 py-2">Source</th>
                                            <th class="px-3 py-2">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach($compound->retentionIndices as $ri)
                                            <tr>
                                                <td class="px-3 py-2">{{ $ri->value }}</td>
                                                <td class="px-3 py-2">{{ $ri->column_type }}</td>
                                                <td class="px-3 py-2">{{ $ri->is_polar ? 'Polar' : 'Non-polar' }}</td>
                                                <td class="px-3 py-2">{{ $ri->source?->name ?? '—' }}</td>
                                                <td class="px-3 py-2">{{ $ri->reference ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No retention indices stored.</p>
                        @endif
                    </section>

                    {{-- ── Disease associations ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Disease associations</h3>
                        @if($compound->diseases->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($compound->diseases as $disease)
                                    <div class="rounded-lg border p-3">
                                        <div class="font-medium text-gray-900">{{ $disease->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $disease->description ?: 'No description.' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Category: {{ $disease->category ?: '—' }}
                                            @if($disease->pivot?->reference)
                                                · Reference: {{ $disease->pivot->reference }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No disease associations stored.</p>
                        @endif
                    </section>

                    {{-- ── Ontologies ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Ontologies</h3>
                        @if($compound->ontologies->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($compound->ontologies as $ontology)
                                    <div class="rounded-lg border p-3">
                                        <div class="font-medium text-gray-900">{{ $ontology->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $ontology->description ?: 'No description.' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Type: {{ $ontology->type ?: '—' }}
                                            @if($ontology->pivot?->reference)
                                                · Reference: {{ $ontology->pivot->reference }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No ontology records stored.</p>
                        @endif
                    </section>

                    {{-- ── Biomarkers ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Biomarkers</h3>
                        @if($compound->biomarkers->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($compound->biomarkers as $biomarker)
                                    <div class="rounded-lg border p-3">
                                        <div class="font-medium text-gray-900">{{ $biomarker->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $biomarker->description ?: 'No description.' }}</div>
                                        @if($biomarker->pivot?->reference)
                                            <div class="mt-1 text-xs text-gray-500">Reference: {{ $biomarker->pivot->reference }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No biomarker records stored.</p>
                        @endif
                    </section>

                    {{-- ── Linked projects ── --}}
                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Linked projects</h3>
                        @if($compound->projects->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-left text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2">Project</th>
                                            <th class="px-3 py-2">Custom name</th>
                                            <th class="px-3 py-2">RI</th>
                                            <th class="px-3 py-2">m/z</th>
                                            <th class="px-3 py-2">Mapped</th>
                                            <th class="px-3 py-2">Duplicate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach($compound->projects as $linkedProject)
                                            <tr class="{{ $linkedProject->id === $project->id ? 'bg-blue-50' : '' }}">
                                                <td class="px-3 py-2 font-medium">{{ $linkedProject->name }}</td>
                                                <td class="px-3 py-2">{{ $linkedProject->pivot->custom_name ?: '—' }}</td>
                                                <td class="px-3 py-2">{{ $linkedProject->pivot->rt ?: '—' }}</td>
                                                <td class="px-3 py-2">{{ $linkedProject->pivot->mz ?: '—' }}</td>
                                                <td class="px-3 py-2">{{ $linkedProject->pivot->is_mapped ? 'Yes' : 'No' }}</td>
                                                <td class="px-3 py-2">{{ $linkedProject->pivot->is_duplicate ? 'Yes' : 'No' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">This compound is not linked to any projects.</p>
                        @endif
                    </section>

                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        This entry has not been mapped to a compound yet. Use the <strong>Map</strong> button to link it.
                    </div>
                @endif

            </div>
        </div>
    </div>
@endif
