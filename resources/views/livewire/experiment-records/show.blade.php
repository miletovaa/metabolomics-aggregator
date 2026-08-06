<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $record->recordTypeLabel() }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Experiment: {{ $experiment->name }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiment</a>
            <a href="{{ route('experiment-records.edit', [$experiment, $record]) }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm hover:opacity-90">
                Edit
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample &amp; context</h2>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <x-compound-info-row label="Sample" :value="$record->sample?->lab_sample_id ?: $record->sample?->external_id" />
            <x-compound-info-row label="Performed by" :value="$record->performedBy?->name" />
            <x-compound-info-row label="Date" :value="$record->performed_at?->format('Y-m-d')" />
            <x-compound-info-row label="Linked to" :value="$record->parentRecord?->recordTypeLabel()" />
        </div>

        @if($record->note)
            <div class="rounded-lg border bg-gray-50 p-3">
                <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Note</div>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $record->note }}</div>
            </div>
        @endif
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Details</h2>

        @if($fields->isEmpty())
            <p class="text-sm text-gray-500">No additional details recorded.</p>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach($fields as $field)
                    <div class="rounded-lg border bg-gray-50 p-3 {{ $field['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                        <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">{{ $field['label'] }}</div>
                        <div class="text-sm text-gray-900 {{ $field['type'] === 'textarea' ? 'whitespace-pre-wrap' : '' }}">{{ $field['value'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @php($childRecords = $record->childRecords()->with(['sample', 'performedBy'])->get())
    @if($childRecords->isNotEmpty())
        <div class="bg-white shadow rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Linked from</h2>
            <div class="space-y-2">
                @foreach($childRecords as $child)
                    <a href="{{ route('experiment-records.show', [$experiment, $child]) }}" wire:navigate class="block rounded-lg border p-3 text-sm hover:bg-gray-50">
                        <div class="font-medium text-gray-900">{{ $child->recordTypeLabel() }}</div>
                        <div class="text-gray-500">{{ $child->performed_at?->format('Y-m-d') ?? '—' }} · {{ $child->performedBy?->name ?? '—' }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
