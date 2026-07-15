<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Sampling</h1>
        <a href="{{ route('samplings.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to samplings</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Sample</h2>
        <p class="text-sm text-gray-700">
            {{ $sampling->sample?->lab_sample_id ?: $sampling->sample?->external_id ?: "#{$sampling->sample_id}" }}
        </p>
    </div>

    @include('livewire.samplings._form')

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('samplings.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
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
