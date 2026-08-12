<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Import Samples</h1>
        <a href="{{ route('samples.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to samples</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6 space-y-4">
        <p class="text-sm text-gray-600">
            Upload a CSV or Excel file with one sample per row. Column headers must match the sample fields
            (<code class="text-xs bg-gray-100 px-1 py-0.5 rounded">sample_group</code>,
            <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">date_received</code>, …) — see
            <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">samples_template.xlsx</code> for the expected
            columns. For fields with multiple values (e.g. <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">feed</code>,
            <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">purpose_of_analysis</code>), separate values with a
            comma (<code class="text-xs bg-gray-100 px-1 py-0.5 rounded">,</code>), e.g. <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">forage, concentrates</code>.
            Rows with an error are skipped and reported below — every other row still imports.
        </p>

        <form wire:submit="import" class="space-y-4">
            <div>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls" class="text-sm">
                @error('file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="text-xs text-gray-500 mt-1">Uploading…</div>
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

            @if($imported > 0)
                <p class="text-sm text-green-700">
                    Imported {{ $imported }} of {{ $total }} row(s).
                </p>
            @else
                <p class="text-sm text-gray-600">
                    Imported 0 of {{ $total }} row(s).
                </p>
            @endif

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