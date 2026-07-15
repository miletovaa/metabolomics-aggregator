<div>
    <div class="max-w-7xl mx-auto px-4 py-6">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Activity Log</h1>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs text-gray-500 mb-1">Search</label>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Search details or subject..."
                       class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black" />
            </div>

            <div class="min-w-44">
                <label class="block text-xs text-gray-500 mb-1">Event type</label>
                <select wire:model.live="filterEvent"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">All events</option>
                    @foreach(\App\Livewire\ActivityLog\Index::EVENT_TYPES as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label class="block text-xs text-gray-500 mb-1">User</label>
                <select wire:model.live="filterUser"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">All users</option>
                    @foreach($users as $uid => $uname)
                        <option value="{{ $uid }}">{{ $uname }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-24">
                <label class="block text-xs text-gray-500 mb-1">Per page</label>
                <select wire:model.live="perPage"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            @if($filterEvent || $filterUser || $search)
                <button wire:click="clearFilters"
                        class="text-sm text-gray-500 hover:text-black underline">
                    Clear filters
                </button>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3 w-44">When</th>
                        <th class="px-4 py-3 w-36">User</th>
                        <th class="px-4 py-3 w-40">Event</th>
                        <th class="px-4 py-3">Details</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $color = $log->eventColor();
                            $colorMap = [
                                'green'  => 'bg-green-100 text-green-800',
                                'blue'   => 'bg-blue-100 text-blue-800',
                                'red'    => 'bg-red-100 text-red-800',
                                'purple' => 'bg-purple-100 text-purple-800',
                                'gray'   => 'bg-gray-100 text-gray-600',
                            ];
                            $badge = $colorMap[$color] ?? $colorMap['gray'];
                        @endphp
                        <tr wire:key="log-{{ $log->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $log->created_at->format('Y-m-d H:i') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $log->user?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                    {{ $log->eventLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $log->details ?? '—' }}
                                @if($log->metadata)
                                    <details class="inline-block ml-2 cursor-pointer">
                                        <summary class="text-xs text-gray-400 hover:text-gray-600 list-none">[details]</summary>
                                        <pre class="mt-1 text-xs bg-gray-50 rounded p-2 text-gray-600 whitespace-pre-wrap max-w-lg">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">No activity logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
            <div class="flex items-center justify-between mt-4 text-sm text-gray-600">
                <div>
                    Page {{ $page }} of {{ $lastPage }}
                    &nbsp;·&nbsp;
                    {{ $logs->total() }} total
                </div>
                <div class="flex gap-2">
                    <button wire:click="previousPage"
                            @class(['px-3 py-1 rounded border', 'opacity-40 cursor-not-allowed' => $page <= 1])
                            @disabled($page <= 1)>
                        &larr; Prev
                    </button>
                    <button wire:click="nextPage"
                            @class(['px-3 py-1 rounded border', 'opacity-40 cursor-not-allowed' => $page >= $lastPage])
                            @disabled($page >= $lastPage)>
                        Next &rarr;
                    </button>
                </div>
            </div>
        @endif

    </div>
</div>
