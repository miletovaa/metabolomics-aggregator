<div class="max-w-4xl mx-auto py-8 space-y-8">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Project</h1>
        <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:underline">Back to projects</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project name <span class="text-red-500">*</span></label>
            <input
                wire:model="name"
                placeholder="e.g. GC-MS experiment #1"
                class="w-full border px-4 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200"
            >
            <p class="text-xs text-gray-500 mt-1">Required</p>
        </div>

        {{-- FILE IMPORT --}}
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Import CSV/XLSX</label>

            <input
                type="file"
                wire:model="file"
                accept=".csv,.xlsx"
                class="block w-full text-sm text-gray-600"
            >

            <div class="text-xs text-gray-500 leading-5">
                <div class="font-medium text-gray-600">Accepted columns (case-insensitive):</div>
                <div><b>name</b> <span class="text-red-500">*</span> — compound name (REQUIRED)</div>
                <div><b>ri</b> — retention index (optional)</div>
                <div><b>mz</b> — mass/charge (optional)</div>
                <div class="mt-2">Only <b>name</b> is required. Other columns can be omitted.</div>
            </div>
        </div>

        {{-- MANUAL INPUT --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700">Manual compounds</label>
                <button
                    type="button"
                    wire:click="addManualRow"
                    class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-lg"
                >
                    + Add row
                </button>
            </div>

            @forelse($manualCompounds as $i => $row)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <input
                        wire:model="manualCompounds.{{ $i }}.name"
                        placeholder="Name *"
                        class="border px-3 py-2 rounded-lg"
                    >

                    <input
                        wire:model="manualCompounds.{{ $i }}.ri"
                        placeholder="RI (optional)"
                        class="border px-3 py-2 rounded-lg"
                    >

                    <input
                        wire:model="manualCompounds.{{ $i }}.mz"
                        placeholder="m/z (optional)"
                        class="border px-3 py-2 rounded-lg"
                    >
                </div>
            @empty
                <p class="text-sm text-gray-500">No manual compounds added yet.</p>
            @endforelse

            <p class="text-xs text-gray-500">Only <b>Name</b> is required. Leave other fields empty if unknown.</p>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3 border-t">
            <a href="{{ route('projects.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
            <button
                type="button"
                wire:click="create"
                wire:loading.attr="disabled"
                wire:target="create"
                class="bg-black text-white px-5 py-2 rounded-lg hover:opacity-90 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="create">Create Project</span>
                <span wire:loading wire:target="create">Creating...</span>
            </button>
        </div>
    </div>

</div>