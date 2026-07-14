<div class="max-w-4xl mx-auto py-8 space-y-6" x-data>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Log Sample</h1>
        <a href="{{ route('samples.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to samples</a>
    </div>

    @include('livewire.samples._form')

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('samples.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
            class="bg-black text-white px-5 py-2 rounded-lg hover:opacity-90 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="save">Save Sample</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</div>
