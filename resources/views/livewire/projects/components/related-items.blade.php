<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <section class="bg-white shadow rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
            Samples ({{ $relatedSamplesCount }})
        </h3>

        @if($relatedSamples->isNotEmpty())
            <div class="space-y-2">
                @foreach($relatedSamples as $sample)
                    <a href="{{ route('samples.edit', $sample) }}" wire:navigate class="block rounded-lg border p-2 text-sm hover:bg-gray-50">
                        <div class="font-medium text-gray-900">{{ $sample->lab_sample_id ?: $sample->external_id ?: "#{$sample->id}" }}</div>
                        <div class="text-gray-500">{{ $sample->groupLabel() ?: '—' }}</div>
                    </a>
                @endforeach
            </div>
            @if($relatedSamplesCount > $relatedSamples->count())
                <a href="{{ route('samples.index') }}" wire:navigate class="mt-2 inline-block text-xs text-blue-600 hover:underline">View all →</a>
            @endif
        @else
            <p class="text-sm text-gray-500">No samples yet.</p>
        @endif
    </section>

    <section class="bg-white shadow rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
            Samplings ({{ $relatedSamplingsCount }})
        </h3>

        @if($relatedSamplings->isNotEmpty())
            <div class="space-y-2">
                @foreach($relatedSamplings as $sampling)
                    <a href="{{ route('samplings.edit', $sampling) }}" wire:navigate class="block rounded-lg border p-2 text-sm hover:bg-gray-50">
                        <div class="font-medium text-gray-900">{{ $sampling->place_of_sampling ?: '—' }}</div>
                        <div class="text-gray-500">{{ $sampling->date_of_sampling?->format('Y-m-d') ?? '—' }}</div>
                    </a>
                @endforeach
            </div>
            @if($relatedSamplingsCount > $relatedSamplings->count())
                <a href="{{ route('samplings.index') }}" wire:navigate class="mt-2 inline-block text-xs text-blue-600 hover:underline">View all →</a>
            @endif
        @else
            <p class="text-sm text-gray-500">No samplings yet.</p>
        @endif
    </section>

    <section class="bg-white shadow rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
            Experiments ({{ $relatedExperimentsCount }})
        </h3>

        @if($relatedExperiments->isNotEmpty())
            <div class="space-y-2">
                @foreach($relatedExperiments as $experiment)
                    <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="block rounded-lg border p-2 text-sm hover:bg-gray-50">
                        <div class="font-medium text-gray-900">{{ $experiment->name }}</div>
                        <div class="text-gray-500">{{ $experiment->statusLabel() }}</div>
                    </a>
                @endforeach
            </div>
            @if($relatedExperimentsCount > $relatedExperiments->count())
                <a href="{{ route('experiments.index') }}" wire:navigate class="mt-2 inline-block text-xs text-blue-600 hover:underline">View all →</a>
            @endif
        @else
            <p class="text-sm text-gray-500">No experiments yet.</p>
        @endif
    </section>
</div>
