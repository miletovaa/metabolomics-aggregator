<div class="max-w-7xl mx-auto px-4 py-6">

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
            <h1 class="text-2xl font-semibold">Experiments</h1>
            <p class="text-sm text-gray-500 mt-1">Conducted experiments, linked to projects and samples.</p>
        </div>
        <a href="{{ route('experiments.create') }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm">
            + Create Experiment
        </a>
    </div>

    <div class="mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by name…"
            class="w-full md:w-80 border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200"
        >
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3">Name</th>
                    <th class="p-3">Project</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Records</th>
                    <th class="p-3">Started</th>
                    <th class="p-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($experiments as $experiment)
                    <tr wire:key="experiment-{{ $experiment->id }}" class="hover:bg-gray-50">
                        <td class="p-3 font-medium text-gray-900">
                            <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="hover:underline">
                                {{ $experiment->name }}
                            </a>
                        </td>
                        <td class="p-3 text-gray-600">{{ $experiment->project?->name ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $experiment->statusLabel() }}</td>
                        <td class="p-3 text-gray-600">{{ $experiment->records_count }}</td>
                        <td class="p-3 text-gray-600">{{ $experiment->started_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-3 text-right">
                            <button
                                wire:click="deleteExperiment({{ $experiment->id }})"
                                wire:confirm="Delete this experiment and all its records? This cannot be undone."
                                class="text-gray-400 hover:text-red-600"
                                title="Delete experiment"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400">
                            No experiments yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $experiments->links() }}
    </div>
</div>
