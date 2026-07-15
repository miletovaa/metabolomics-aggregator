<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Log Sampling</h1>
        <a href="{{ route('samplings.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to samplings</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample</h2>
        <div class="md:w-1/2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sample <span class="text-red-500">*</span></label>
            <select wire:model="sampleId" class="w-full border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Select a sample without a sampling record yet…</option>
                @foreach($availableSamples as $sample)
                    <option value="{{ $sample->id }}">
                        {{ $sample->lab_sample_id ?: $sample->external_id ?: "#{$sample->id}" }}
                    </option>
                @endforeach
            </select>
            @error('sampleId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-500 mt-1">Only samples that don't already have a sampling record are listed.</p>
        </div>
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
            <span wire:loading.remove wire:target="save">Save Sampling</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</div>
