<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $record->recordTypeLabel() }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Experiment: {{ $experiment->name }}
                @if($batch->count() > 1)
                    &middot; {{ $batch->count() }} subjects in this batch
                @endif
            </p>
        </div>
        <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiment</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample &amp; context</h2>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <x-compound-info-row label="Sample" :value="$record->sample?->lab_sample_id ?: $record->sample?->external_id" />
            <x-compound-info-row label="Performed by" :value="$record->performedBy?->name" />
            <x-compound-info-row label="Date" :value="$record->performed_at?->format('Y-m-d')" />
        </div>
    </div>

    @foreach($batch as $item)
        @php($item_record = $item['record'])
        <div class="bg-white shadow rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">
                    {{ $item['subject'] ?? $item_record->recordTypeLabel() }}
                </h2>
                <a href="{{ route('experiment-records.edit', [$experiment, $item_record]) }}" wire:navigate class="text-xs text-gray-500 hover:underline">
                    Edit
                </a>
            </div>

            @if($item_record->parentRecord)
                <x-compound-info-row label="Linked to" :value="$item_record->parentRecord->recordTypeLabel()" />
            @endif

            @if($item_record->note)
                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Note</div>
                    <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $item_record->note }}</div>
                </div>
            @endif

            @if($item['fields']->isEmpty())
                <p class="text-sm text-gray-500">No additional details recorded.</p>
            @else
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach($item['fields'] as $field)
                        <div class="rounded-lg border bg-gray-50 p-3 {{ $field['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                            <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">{{ $field['label'] }}</div>
                            <div class="text-sm text-gray-900 {{ $field['type'] === 'textarea' ? 'whitespace-pre-wrap' : '' }}">{{ $field['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($item['children']->isNotEmpty())
                <div class="pt-2 border-t">
                    <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Linked from</div>
                    <div class="space-y-2">
                        @foreach($item['children'] as $child)
                            <a href="{{ route('experiment-records.show', [$experiment, $child]) }}" wire:navigate class="block rounded-lg border p-3 text-sm hover:bg-gray-50">
                                <div class="font-medium text-gray-900">{{ $child->recordTypeLabel() }}</div>
                                <div class="text-gray-500">{{ $child->performed_at?->format('Y-m-d') ?? '—' }} · {{ $child->performedBy?->name ?? '—' }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
