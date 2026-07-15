<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Edit Record</h1>
            <p class="text-sm text-gray-500 mt-1">
                Experiment: {{ $experiment->name }} &middot; {{ $record->recordTypeLabel() }}
            </p>
        </div>
        <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiment</a>
    </div>

    @include('livewire.experiment-records._form')

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
            class="bg-black text-white px-5 py-2 rounded-lg hover:opacity-90 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="save">Save Changes</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</div>
