@php
    $inputClass = 'w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200 text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

{{-- Identification --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Identification</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelClass }}">Sample lab ID</label>
            <input wire:model="labSampleId" class="{{ $inputClass }}" placeholder="e.g. LAB-0001">
        </div>
        <div>
            <label class="{{ $labelClass }}">External ID</label>
            <input wire:model="externalId" class="{{ $inputClass }}" placeholder="Collaborator / source ID">
        </div>
        <div>
            <label class="{{ $labelClass }}">Date received</label>
            <input type="date" wire:model="dateReceived" class="{{ $inputClass }}">
            @error('dateReceived') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelClass }}">Group <span class="text-red-500">*</span></label>
            <select wire:model.live="group" class="{{ $inputClass }}">
                <option value="">Select group…</option>
                @foreach($groups as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('group') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Subgroup</label>
            <select wire:model="subgroup" class="{{ $inputClass }}" @disabled(!$group)>
                <option value="">Select subgroup…</option>
                @foreach($subgroupOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('subgroup') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Matrix name</label>
            <input wire:model="matrixName" class="{{ $inputClass }}" placeholder="e.g. Seabream fillet">
            <p class="text-xs text-gray-500 mt-1">What exactly is in the sample — e.g. "Seabream fillet" or "Edible part of Mediterranean mussel".</p>
        </div>
    </div>
</div>

@if(in_array($group, \App\Models\Sample::TYPE_DETAIL_GROUPS, true))
{{-- Sample type details --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample type details</h2>

    @if($group === 'plant')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="{{ $labelClass }}">Latin name of plant</label>
                <input wire:model="typeDetails.latin_name" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Part of plant</label>
                <select wire:model="typeDetails.part_of_plant" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($partOfPlantOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Harvest year</label>
                <input type="number" wire:model="typeDetails.harvest_year" class="{{ $inputClass }}" placeholder="e.g. 2025">
            </div>
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <select wire:model="typeDetails.status" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Producer</label>
                <select wire:model="typeDetails.producer" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($plantProducerOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Production type</label>
                <select wire:model="typeDetails.production_type" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($productionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Declared country of origin</label>
                <input wire:model="typeDetails.declared_country_of_origin" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Country of origin of raw material</label>
                <input wire:model="typeDetails.country_of_origin_of_raw_material" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Region of origin</label>
                <input wire:model="typeDetails.region_of_origin" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Irrigation</label>
                <select wire:model="typeDetails.irrigation" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Source of water</label>
                <select wire:model="typeDetails.source_of_water" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($sourceOfWaterOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Processing type</label>
                <select wire:model="typeDetails.processing_type" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($plantProcessingTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="{{ $labelClass }}">Note</label>
                <textarea wire:model="typeDetails.note" rows="2" class="{{ $inputClass }}"></textarea>
            </div>
        </div>
    @elseif($group === 'animal')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="{{ $labelClass }}">Common name of animal</label>
                <input wire:model="typeDetails.common_name" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Latin name</label>
                <input wire:model="typeDetails.latin_name" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Breed</label>
                <input wire:model="typeDetails.breed" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Part of animal</label>
                <select wire:model="typeDetails.part_of_animal" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($partOfAnimalOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <select wire:model="typeDetails.status" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Producer</label>
                <select wire:model="typeDetails.producer" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Production type</label>
                <select wire:model="typeDetails.production_type" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($productionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Country of origin</label>
                <input wire:model="typeDetails.country_of_origin" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Region of origin</label>
                <input wire:model="typeDetails.region_of_origin" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Source of drinking water</label>
                <select wire:model="typeDetails.source_of_drinking_water" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($sourceOfWaterOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Processing type</label>
                <select wire:model="typeDetails.processing_type" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($animalProcessingTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="{{ $labelClass }}">Feed</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
                    @foreach($animalFeedTypes as $key => $label)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="typeDetails.feed" value="{{ $key }}" class="rounded border-gray-300">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="md:col-span-3">
                <label class="{{ $labelClass }}">Note</label>
                <textarea wire:model="typeDetails.note" rows="2" class="{{ $inputClass }}"></textarea>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Storage --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Storage</h2>

    <div>
        <label class="{{ $labelClass }}">Storage conditions</label>
        <select wire:model="storageCondition" class="{{ $inputClass }} md:w-1/2">
            <option value="">Select storage condition…</option>
            @foreach($storageConditions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="{{ $labelClass }}">Storage condition details</label>
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            @foreach($storageConditionDetailsOptions as $key => $label)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="storageConditionDetails" value="{{ $key }}" class="rounded border-gray-300">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- Assignment --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Assignment</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Responsible analyst</label>
            <select wire:model="responsibleAnalystId" class="{{ $inputClass }}">
                <option value="">Select analyst…</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div
            x-data="{
                open: false,
                query: @js($selectedProjectName),
                projects: @js($projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
                get filtered() {
                    if (!this.query) return this.projects;
                    return this.projects.filter(p => p.name.toLowerCase().includes(this.query.toLowerCase()));
                },
                get exactMatch() {
                    return this.projects.some(p => p.name.toLowerCase() === this.query.toLowerCase());
                },
                select(p) {
                    this.query = p.name;
                    $wire.set('projectId', p.id);
                    $wire.set('newProjectName', '');
                    this.open = false;
                },
                createNew() {
                    $wire.set('projectId', null);
                    $wire.set('newProjectName', this.query);
                    this.open = false;
                },
                clear() {
                    this.query = '';
                    $wire.set('projectId', null);
                    $wire.set('newProjectName', '');
                }
            }"
            @click.outside="open = false"
            class="relative"
        >
            <label class="{{ $labelClass }}">Project</label>
            <input
                x-model="query"
                @focus="open = true"
                @input="open = true"
                type="text"
                class="{{ $inputClass }}"
                placeholder="Search or create a project…"
                autocomplete="off"
            >
            <button type="button" x-show="query" @click="clear" class="absolute right-2 top-8 text-gray-400 hover:text-gray-600 text-xs">✕</button>

            <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg shadow-lg border max-h-56 overflow-y-auto">
                <template x-for="p in filtered" :key="p.id">
                    <div @click="select(p)" class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" x-text="p.name"></div>
                </template>
                <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">No matching projects</div>
                <div
                    x-show="query && !exactMatch"
                    @click="createNew"
                    class="px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 cursor-pointer border-t"
                >
                    + Create project "<span x-text="query"></span>"
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Type a name; pick an existing project or create a new one.</p>
        </div>
    </div>
</div>

{{-- Analysis --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Analysis</h2>

    <div>
        <label class="{{ $labelClass }}">Purpose of analysis</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
            @foreach($purposesOfAnalysis as $key => $label)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="purposeOfAnalysis" value="{{ $key }}" class="rounded border-gray-300">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}">Planned analysis</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
            @foreach($plannedAnalyses as $key => $label)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="plannedAnalysis" value="{{ $key }}" class="rounded border-gray-300">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Notes</h2>
    <textarea wire:model="note" rows="3" class="{{ $inputClass }}" placeholder="Additional notes about this sample…"></textarea>
</div>
