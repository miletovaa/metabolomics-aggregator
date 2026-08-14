<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Predefined Values</h1>
        <p class="text-sm text-gray-500 mt-1">Manage the dropdown options used across samples, samplings, experiments, and projects.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($lists as $list)
            <a
                href="{{ route('option-lists.show', $list) }}"
                wire:navigate
                class="bg-white shadow rounded-xl p-4 hover:shadow-md transition-shadow flex items-center justify-between"
            >
                <div>
                    <p class="font-medium text-gray-900">{{ $list->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $list->values_count }} value{{ $list->values_count === 1 ? '' : 's' }}
                        @if($list->is_nested && $list->parentList)
                            &middot; nested under {{ $list->parentList->name }}
                        @endif
                    </p>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endforeach
    </div>
</div>
