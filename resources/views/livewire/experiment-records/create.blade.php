<div class="max-w-4xl mx-auto py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Add Record</h1>
            <p class="text-sm text-gray-500 mt-1">Experiment: {{ $experiment->name }}</p>
        </div>
        <a href="{{ route('experiments.show', $experiment) }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to experiment</a>
    </div>

    <div class="bg-white shadow rounded-2xl p-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Record type <span class="text-red-500">*</span></label>
        <select wire:model.live="recordType" class="w-full md:w-1/2 border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-200">
            <option value="">Select record type…</option>
            @foreach(\App\Models\ExperimentRecord::FAMILY_LABELS as $family => $familyLabel)
                <optgroup label="{{ $familyLabel }}">
                    @foreach(\App\Models\ExperimentRecord::FAMILIES[$family] as $type)
                        <option value="{{ $type }}">{{ \App\Models\ExperimentRecord::RECORD_TYPES[$type] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('recordType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

        @if(in_array($recordType, \App\Models\ExperimentRecord::COMPOUND_RESULT_TYPES, true))
            <p class="text-xs text-gray-500 mt-2">
                You'll be taken to the project's compound list to add or import the measured compounds for this run.
            </p>
        @endif
    </div>

    @if($recordType)
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
                @if(in_array($recordType, \App\Models\ExperimentRecord::COMPOUND_RESULT_TYPES, true))
                    <span wire:loading.remove wire:target="save">Continue →</span>
                @else
                    <span wire:loading.remove wire:target="save">Save Record</span>
                @endif
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    @endif
</div>
