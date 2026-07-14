<div class="max-w-7xl mx-auto px-4 py-6">

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
        <a href="{{ route('samples.create') }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm">
            + Log Sample
        </a>
    </div>

    <div class="mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by lab ID, external ID, or matrix…"
            class="w-full md:w-80 border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200"
        >
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3">Lab ID</th>
                    <th class="p-3">External ID</th>
                    <th class="p-3">Group</th>
                    <th class="p-3">Subgroup</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Date received</th>
                    <th class="p-3">Project</th>
                    <th class="p-3">Analyst</th>
                    <th class="p-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($samples as $sample)
                    <tr wire:key="sample-{{ $sample->id }}" class="hover:bg-gray-50">
                        <td class="p-3 font-medium text-gray-900">
                            <a href="{{ route('samples.edit', $sample) }}" wire:navigate class="hover:underline">
                                {{ $sample->lab_sample_id ?: '—' }}
                            </a>
                        </td>
                        <td class="p-3 text-gray-600">{{ $sample->external_id ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->groupLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->subgroupLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->sampleTypeLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->date_received?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->project?->name ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sample->responsibleAnalyst?->name ?? '—' }}</td>
                        <td class="p-3 text-right">
                            <button
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
                        <td colspan="9" class="p-8 text-center text-gray-400">
                            No samples logged yet.
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
