@if($showMappingLogModal && !empty($mappingLog))
    @php
        $total    = $mappingLog['total']       ?? 0;
        $local    = $mappingLog['local_found'] ?? 0;
        $pubchem  = $mappingLog['pubchem_new'] ?? 0;
        $notFound = $mappingLog['not_found']   ?? 0;
        $mapped   = $local + $pubchem;
    @endphp
    <div
        x-data="{ open: true }"
        x-show="open"
        @keydown.escape.window="open = false; $wire.closeMappingLogModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
        <div class="absolute inset-0" @click="open = false; $wire.closeMappingLogModal()"></div>

        <div
            class="relative w-full max-w-md rounded-2xl bg-white shadow-xl"
            @click.stop
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Mapping complete</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $mappingLog['ran_at'] }}</p>
                </div>
                <button
                    type="button"
                    @click="open = false; $wire.closeMappingLogModal()"
                    class="rounded-lg px-3 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                >✕</button>
            </div>

            {{-- Stats --}}
            <div class="px-6 py-5 space-y-3">

                <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                    <span class="text-sm text-gray-600">Total processed</span>
                    <span class="font-semibold text-gray-900">{{ $total }}</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-green-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-green-800">Found in local database</p>
                        <p class="text-xs text-green-600">Linked to an existing compound record</p>
                    </div>
                    <span class="text-xl font-bold text-green-700">{{ $local }}</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-blue-800">New via PubChem pipeline</p>
                        <p class="text-xs text-blue-600">Created as a new compound record</p>
                    </div>
                    <span class="text-xl font-bold text-blue-700">{{ $pubchem }}</span>
                </div>

                @if($notFound > 0)
                    <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-amber-800">Not matched</p>
                            <p class="text-xs text-amber-600">Use the row-level Map button to retry individually</p>
                        </div>
                        <span class="text-xl font-bold text-amber-700">{{ $notFound }}</span>
                    </div>
                @endif

                @if($total > 0)
                    <div class="pt-1">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Mapped</span>
                            <span>{{ $mapped }} / {{ $total }}</span>
                        </div>
                        <div class="w-full rounded-full bg-gray-200 h-2">
                            <div
                                class="rounded-full h-2 bg-green-500 transition-all"
                                style="width: {{ $total > 0 ? round($mapped / $total * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="border-t px-6 py-4 flex justify-end">
                <button
                    type="button"
                    @click="open = false; $wire.closeMappingLogModal()"
                    class="rounded-lg bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-700"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
@endif
