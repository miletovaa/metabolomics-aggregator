<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Import Elemental Composition Results</h1>
            <p class="text-sm text-gray-500 mt-1">Experiment: {{ $experiment->name }}</p>
        </div>
        <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiment</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <p class="text-sm text-gray-600">
            Upload an ICP-MS multi-element analysis report (one sheet: a per-element QC header, one or more
            reference-standard blocks, then a per-sample results table). Each sample row must match an
            existing sample's lab sample ID or external ID. For every matched sample this creates one
            Analysis — elemental composition record (carrying the run's QC/standards data) and one
            Result — elemental composition record per element.
        </p>

        <form wire:submit="import" class="space-y-4">
            <div>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls" class="text-sm">
                @error('file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="text-xs text-gray-500 mt-1">Uploading…</div>
            </div>

            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-1">Performed by</label>
                <select wire:model="performedBy" class="w-full border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">Select…</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="import"
                class="bg-black text-white px-5 py-2 rounded-lg text-sm hover:opacity-90 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="import">Import</span>
                <span wire:loading wire:target="import">Importing…</span>
            </button>
        </form>
    </div>

    @if($total !== null)
        <div class="bg-white shadow rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Result</h2>

            <p class="text-sm {{ $importedSamples > 0 ? 'text-green-700' : 'text-gray-600' }}">
                Imported {{ $importedSamples }} of {{ $total }} sample row(s).
                @if($importedSamples > 0)
                    <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="underline">View experiment →</a>
                @endif
            </p>

            @if(count($rowErrors) > 0)
                <div>
                    <h3 class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2">
                        {{ count($rowErrors) }} row(s) with errors
                    </h3>
                    <div class="divide-y divide-gray-100 border rounded-lg overflow-hidden">
                        @foreach($rowErrors as $error)
                            <div class="p-3 text-sm">
                                <span class="font-medium text-gray-900">Row {{ $error['row'] }}:</span>
                                <ul class="list-disc list-inside text-red-700 mt-1">
                                    @foreach($error['messages'] as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
