@php
    $inputClass = 'w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200 text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

<div class="max-w-4xl mx-auto py-8 space-y-6" x-data>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Experiment</h1>
        <a href="{{ route('experiments.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiments</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <div>
            <label class="{{ $labelClass }}">Name <span class="text-red-500">*</span></label>
            <input wire:model="name" class="{{ $inputClass }}" placeholder="e.g. Honey origin authentication 2026">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelClass }}">Description</label>
            <textarea wire:model="description" rows="2" class="{{ $inputClass }}"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <select wire:model="status" class="{{ $inputClass }}">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Started</label>
                <input type="date" wire:model="startedAt" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Completed</label>
                <input type="date" wire:model="completedAt" class="{{ $inputClass }}">
            </div>
        </div>

        <div
            x-data="{
                open: false,
                query: '',
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
                }
            }"
            @click.outside="open = false"
            class="relative md:w-1/2"
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
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('experiments.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
            class="bg-black text-white px-5 py-2 rounded-lg hover:opacity-90 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="save">Create Experiment</span>
            <span wire:loading wire:target="save">Creating…</span>
        </button>
    </div>
</div>
