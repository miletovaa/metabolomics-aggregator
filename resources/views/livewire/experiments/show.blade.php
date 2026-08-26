<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

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

    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('experiments.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">← Back to experiments</a>
            <h1 class="text-2xl font-semibold mt-1">{{ $experiment->name }}</h1>
            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                <span>{{ $experiment->statusLabel() }}</span>
                @if($experiment->project)
                    <span>· Project: {{ $experiment->project->name }}</span>
                @endif
                <span>· {{ $samples->count() }} sample(s)</span>
            </div>
            @if($experiment->description)
                <p class="text-sm text-gray-600 mt-2 max-w-2xl">{{ $experiment->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('experiment-records.import-elemental-composition', $experiment) }}" wire:navigate class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm whitespace-nowrap hover:bg-gray-50">
                Import Elemental Composition
            </a>
            <a href="{{ route('experiment-records.create', $experiment) }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                + Add Record
            </a>
        </div>
    </div>

    @forelse(\App\Models\ExperimentRecord::FAMILY_LABELS as $family => $familyLabel)
        @php($familyGroups = $recordGroupsByFamily->get($family, collect()))
        @php($familyCompoundGroups = $family === 'result' ? $compoundResultGroups : collect())
        <div class="bg-white shadow rounded-2xl overflow-hidden">
            <div class="px-6 py-3 bg-gray-50 border-b">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ $familyLabel }}</h2>
            </div>

            @if($familyGroups->isEmpty() && $familyCompoundGroups->isEmpty())
                <div class="px-6 py-6 text-sm text-gray-400">No {{ strtolower($familyLabel) }} records yet.</div>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-500 border-b">
                        <tr>
                            <th class="p-3 font-medium">Type</th>
                            <th class="p-3 font-medium">Sample</th>
                            <th class="p-3 font-medium">Performed by</th>
                            <th class="p-3 font-medium">Date</th>
                            <th class="p-3 font-medium">Linked to</th>
                            <th class="p-3 font-medium">Subject</th>
                            <th class="p-3 font-medium">Value</th>
                            <th class="p-3 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($familyGroups as $group)
                            @foreach($group as $record)
                                @php($recordShowUrl = route('experiment-records.show', [$experiment, $record]))
                                <tr
                                    wire:key="record-{{ $record->id }}"
                                    x-on:click="Livewire.navigate('{{ $recordShowUrl }}')"
                                    class="hover:bg-gray-50 cursor-pointer"
                                >
                                    @if($loop->first)
                                        <td class="p-3 text-gray-900 font-medium align-top" rowspan="{{ $group->count() }}">
                                            {{ $record->recordTypeLabel() }}
                                        </td>
                                        <td class="p-3 text-gray-600 align-top" rowspan="{{ $group->count() }}">{{ $record->sample?->lab_sample_id ?: $record->sample?->external_id ?: '—' }}</td>
                                        <td class="p-3 text-gray-600 align-top" rowspan="{{ $group->count() }}">{{ $record->performedBy?->name ?? '—' }}</td>
                                        <td class="p-3 text-gray-600 align-top" rowspan="{{ $group->count() }}">{{ $record->performed_at?->format('Y-m-d') ?? '—' }}</td>
                                        <td class="p-3 text-gray-600 align-top" rowspan="{{ $group->count() }}">{{ $record->parentRecord?->recordTypeLabel() ?? '—' }}</td>
                                    @endif
                                    <td class="p-3 text-gray-600">
                                        <a href="{{ $recordShowUrl }}" wire:navigate class="hover:underline">
                                            {{ $record->subjectLabel() ?? 'View details →' }}
                                        </a>
                                    </td>
                                    <td class="p-3 text-gray-600">{{ $record->valueLabel() ?? '—' }}</td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        @if($family === 'analysis' && ($resultType = \App\Models\ExperimentRecord::ANALYSIS_RESULT_TYPES[$record->record_type] ?? null))
                                            @php($addResultsUrl = in_array($resultType, \App\Models\ExperimentRecord::COMPOUND_RESULT_TYPES, true)
                                                ? route('experiments.results', ['experiment' => $experiment->id, 'sampleId' => $record->sample_id, 'recordType' => $resultType, 'parentRecordId' => $record->id])
                                                : route('experiment-records.create', ['experiment' => $experiment, 'sampleId' => $record->sample_id, 'recordType' => $resultType, 'parentRecordId' => $record->id]))
                                            <a
                                                href="{{ $addResultsUrl }}"
                                                wire:navigate
                                                x-on:click.stop
                                                class="text-xs text-blue-600 hover:underline mr-2"
                                            >
                                                Add results
                                            </a>
                                        @endif
                                        <button
                                            x-on:click.stop
                                            wire:click="deleteRecord({{ $record->id }})"
                                            wire:confirm="Delete this record?"
                                            class="text-gray-400 hover:text-red-600"
                                            title="Delete record"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @foreach($familyCompoundGroups as $group)
                            @php($runUrl = route('experiments.results', [
                                'experiment' => $experiment->id,
                                'sampleId' => $group->sample_id,
                                'recordType' => $group->record_type,
                                'performedBy' => $group->performed_by,
                                'performedAt' => $group->performed_at?->format('Y-m-d'),
                                'parentRecordId' => $group->parentRecord?->id,
                            ]))
                            <tr
                                wire:key="compound-group-{{ $loop->index }}"
                                x-on:click="Livewire.navigate('{{ $runUrl }}')"
                                class="hover:bg-blue-50 cursor-pointer"
                            >
                                <td class="p-3 text-gray-900 font-medium">
                                    {{ \App\Models\ExperimentRecord::RECORD_TYPES[$group->record_type] ?? $group->record_type }}
                                </td>
                                <td class="p-3 text-gray-600">{{ $group->sample?->lab_sample_id ?: $group->sample?->external_id ?: '—' }}</td>
                                <td class="p-3 text-gray-600">{{ $group->performedBy?->name ?? '—' }}</td>
                                <td class="p-3 text-gray-600">{{ $group->performed_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="p-3 text-gray-600">{{ $group->parentRecord?->recordTypeLabel() ?? '—' }}</td>
                                <td class="p-3 text-gray-600" colspan="2">
                                    <a href="{{ $runUrl }}" wire:navigate class="text-blue-600 hover:underline">
                                        View {{ $group->compound_count }} result{{ $group->compound_count === 1 ? '' : 's' }} →
                                    </a>
                                </td>
                                <td class="p-3"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</div>
