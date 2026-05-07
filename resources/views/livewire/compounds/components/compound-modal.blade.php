@if($showModal && $selectedCompound)
    <div
        x-data="{ open: true }"
        x-show="open"
        @keydown.escape.window="open = false; $wire.closeCompoundModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        wire:key="compound-modal-{{ $selectedCompound->id }}-{{ $showModal ? 'open' : 'closed' }}"
    >
        <div 
            class="absolute inset-0"
            @click="open = false; $wire.closeCompoundModal()"
        ></div>

        <div
            class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl"
            @click.stop
            x-show="open"
        >
            <div class="sticky top-0 z-10 flex items-start justify-between border-b bg-white px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ $selectedCompound->canonical_name }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Compound ID: {{ $selectedCompound->id }}
                    </p>
                </div>

                <div class="flex items-center">
                    <button
                        type="button"
                        @click="open = false; $wire.closeCompoundModal()"
                        class="rounded-lg px-3 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div class="space-y-6 px-6 py-5">
                {{-- Core information --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Core information
                    </h3>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <x-compound-info-row label="Canonical name" :value="$selectedCompound->canonical_name" />
                        <x-compound-info-row label="IUPAC name" :value="$selectedCompound->iupac_name" />
                        <x-compound-info-row label="Molecular formula" :value="$selectedCompound->molecular_formula" />
                        <x-compound-info-row label="SMILES" :value="$selectedCompound->smiles" mono />
                        <x-compound-info-row label="InChI" :value="$selectedCompound->inchi" mono />
                        <x-compound-info-row label="InChIKey" :value="$selectedCompound->inchikey" mono />
                        <x-compound-info-row label="PubChem CID" :value="$selectedCompound->pubchem_cid" />
                        <x-compound-info-row label="CAS" :value="$selectedCompound->cas" />
                        <x-compound-info-row label="HMDB ID" :value="$selectedCompound->hmdb_id" />
                        <x-compound-info-row label="ChEBI ID" :value="$selectedCompound->chebi_id" />
                    </div>
                </section>

                {{-- Synonyms --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Synonyms
                    </h3>

                    @if($selectedCompound->synonyms->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedCompound->synonyms as $synonym)
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

                {{-- Taxonomy --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Taxonomy
                    </h3>

                    @if($selectedCompound->taxonomy)
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <x-compound-info-row label="Kingdom" :value="$selectedCompound->taxonomy->kingdom" />
                            <x-compound-info-row label="Superclass" :value="$selectedCompound->taxonomy->superclass" />
                            <x-compound-info-row label="Class" :value="$selectedCompound->taxonomy->class" />
                            <x-compound-info-row label="Subclass" :value="$selectedCompound->taxonomy->subclass" />
                            <x-compound-info-row label="Direct parent" :value="$selectedCompound->taxonomy->direct_parent" />
                            <x-compound-info-row label="Alternative parents" :value="$selectedCompound->taxonomy->alternative_parents" />
                        </div>

                        @if($selectedCompound->taxonomy->raw_json)
                            <details class="mt-4 rounded-lg border bg-gray-50 p-3">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700">
                                    Raw taxonomy JSON
                                </summary>
                                <pre class="mt-3 overflow-x-auto text-xs text-gray-700">{{ json_encode($selectedCompound->taxonomy->raw_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @endif
                    @else
                        <p class="text-sm text-gray-500">No taxonomy stored.</p>
                    @endif
                </section>

                {{-- Retention indices --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Retention indices
                    </h3>

                    @if($selectedCompound->retentionIndices->isNotEmpty())
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
                                    @foreach($selectedCompound->retentionIndices as $ri)
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

                {{-- Diseases --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Disease associations
                    </h3>

                    @if($selectedCompound->diseases->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($selectedCompound->diseases as $disease)
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

                {{-- Ontologies --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Ontologies
                    </h3>

                    @if($selectedCompound->ontologies->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($selectedCompound->ontologies as $ontology)
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

                {{-- Biomarkers --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Biomarkers
                    </h3>

                    @if($selectedCompound->biomarkers->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($selectedCompound->biomarkers as $biomarker)
                                <div class="rounded-lg border p-3">
                                    <div class="font-medium text-gray-900">{{ $biomarker->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $biomarker->description ?: 'No description.' }}</div>
                                    @if($biomarker->pivot?->reference)
                                        <div class="mt-1 text-xs text-gray-500">
                                            Reference: {{ $biomarker->pivot->reference }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No biomarker records stored.</p>
                    @endif
                </section>

                {{-- Projects --}}
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Linked projects
                    </h3>

                    @if($selectedCompound->projects->isNotEmpty())
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2">Project</th>
                                        <th class="px-3 py-2">Custom name</th>
                                        <th class="px-3 py-2">m/z</th>
                                        <th class="px-3 py-2">RT</th>
                                        <th class="px-3 py-2">Mapped</th>
                                        <th class="px-3 py-2">Duplicate</th>
                                        <th class="px-3 py-2">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($selectedCompound->projects as $project)
                                        <tr>
                                            <td class="px-3 py-2">{{ $project->name }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->custom_name ?: '—' }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->mz ?: '—' }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->rt ?: '—' }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->is_mapped ? 'Yes' : 'No' }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->is_duplicate ? 'Yes' : 'No' }}</td>
                                            <td class="px-3 py-2">{{ $project->pivot->notes ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">This compound is not linked to any projects.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endif