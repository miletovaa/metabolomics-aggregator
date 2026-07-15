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
            <h1 class="text-2xl font-semibold">Samplings</h1>
            <p class="text-sm text-gray-500 mt-1">Collection-event details for samples: when, where, how, and by whom.</p>
        </div>
        <a href="{{ route('samplings.create') }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm">
            + Log Sampling
        </a>
    </div>

    <div class="mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by sample lab ID, country, or place…"
            class="w-full md:w-80 border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200"
        >
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3">Sample</th>
                    <th class="p-3">Date of sampling</th>
                    <th class="p-3">Country</th>
                    <th class="p-3">Place</th>
                    <th class="p-3">Method</th>
                    <th class="p-3">Collector</th>
                    <th class="p-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($samplings as $sampling)
                    <tr wire:key="sampling-{{ $sampling->id }}" class="hover:bg-gray-50">
                        <td class="p-3 font-medium text-gray-900">
                            <a href="{{ route('samplings.edit', $sampling) }}" wire:navigate class="hover:underline">
                                {{ $sampling->sample?->lab_sample_id ?: $sampling->sample?->external_id ?: "#{$sampling->sample_id}" }}
                            </a>
                        </td>
                        <td class="p-3 text-gray-600">{{ $sampling->date_of_sampling?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sampling->country_of_sampling ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sampling->place_of_sampling ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sampling->samplingMethodLabel() ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $sampling->collector ?: '—' }}</td>
                        <td class="p-3 text-right">
                            <button
                                wire:click="deleteSampling({{ $sampling->id }})"
                                wire:confirm="Delete this sampling record? This cannot be undone."
                                class="text-gray-400 hover:text-red-600"
                                title="Delete sampling"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No samplings logged yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $samplings->links() }}
    </div>
</div>
