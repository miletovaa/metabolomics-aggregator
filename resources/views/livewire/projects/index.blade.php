<div>
    <div class="max-w-7xl mx-auto px-4 py-6">

        {{-- Toast notification --}}
        @if($successMessage || $errorMessage)
            <div
                x-data="{ visible: true }"
                x-init="setTimeout(() => { visible = false; $wire.dismissNotification() }, 3500)"
                x-show="visible"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium
                    {{ $successMessage ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}"
            >
                @if($successMessage)
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $successMessage }}
                @else
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ $errorMessage }}
                @endif
                <button wire:click="dismissNotification" class="ml-1 opacity-60 hover:opacity-100">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="flex justify-between mb-6">
            <h1 class="text-2xl font-semibold">Projects</h1>

            <a href="{{ route('projects.create') }}"
               class="bg-black text-white px-4 py-2 rounded-lg text-sm">
                Create Project
            </a>
        </div>

        <div class="bg-white rounded-xl shadow">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 rounded-xl border-b">
                    <tr class="text-left text-gray-700">
                        <th class="p-3">Name</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Created</th>
                        <th class="p-3 w-10"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($projects as $project)
                        <tr wire:key="project-{{ $project->id }}"
                            class="border-t hover:bg-gray-50 group cursor-pointer"
                            x-data="{
                                editing: false,
                                name: @js($project->name),
                                original: @js($project->name),
                                cancelled: false,
                                navTimer: null,
                                handleClick() {
                                    if (this.editing) return;
                                    clearTimeout(this.navTimer);
                                    this.navTimer = setTimeout(() => window.location = '{{ route('projects.show', $project) }}', 250);
                                },
                                handleDblClick() {
                                    clearTimeout(this.navTimer);
                                },
                                startEdit() {
                                    clearTimeout(this.navTimer);
                                    this.editing = true;
                                    this.$nextTick(() => this.$refs.nameInput.select());
                                },
                                cancel() {
                                    this.cancelled = true;
                                    this.name = this.original;
                                    this.editing = false;
                                },
                                save() {
                                    if (!this.cancelled && this.name.trim() !== '' && this.name !== this.original) {
                                        $wire.saveName({{ $project->id }}, this.name);
                                        this.original = this.name;
                                    }
                                    this.cancelled = false;
                                    this.editing = false;
                                }
                            }"
                            @click="handleClick()"
                            @dblclick="handleDblClick()"
                        >
                            {{-- Name — double-click to rename --}}
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        x-show="!editing"
                                        @dblclick="startEdit()"
                                        title="Double-click to rename"
                                    >{{ $project->name }}</span>

                                    <input
                                        x-show="editing"
                                        x-ref="nameInput"
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
                                        class="opacity-0 group-hover:opacity-40 hover:!opacity-100 transition-opacity shrink-0"
                                        title="Rename"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            {{-- Status dropdown — @click.stop prevents row navigation --}}
                            <td class="p-3" @click.stop>
                                <select
                                    wire:change="updateStatus({{ $project->id }}, $event.target.value)"
                                    class="text-xs font-medium rounded-full px-2.5 py-1 border-0 cursor-pointer focus:ring-2 focus:ring-black focus:outline-none
                                        {{ $project->status === 'active'    ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $project->status === 'completed' ? 'bg-blue-100 text-blue-800'  : '' }}
                                        {{ $project->status === 'archived'  ? 'bg-gray-100 text-gray-600'  : '' }}"
                                >
                                    @foreach(\App\Livewire\Projects\Index::STATUSES as $status)
                                        <option value="{{ $status }}" @selected($project->status === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="p-3 text-gray-500">
                                {{ $project->created_at->format('M d, Y') }}
                            </td>

                            {{-- Delete — @click.stop prevents row navigation --}}
                            <td class="p-3 text-right" @click.stop>
                                <button
                                    wire:click="deleteProject({{ $project->id }})"
                                    wire:confirm="Delete '{{ addslashes($project->name) }}'? This cannot be undone."
                                    class="opacity-0 group-hover:opacity-40 hover:!opacity-100 hover:text-red-600 transition-opacity"
                                    title="Delete project"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3M3 7h18"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-400">No projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
