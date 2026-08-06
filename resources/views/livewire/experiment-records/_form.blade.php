@php
    $inputClass = 'w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200 text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample &amp; context</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelClass }}">Sample <span class="text-red-500">*</span></label>
            <select wire:model="sampleId" class="{{ $inputClass }}">
                <option value="">Select sample…</option>
                @foreach($samples as $sample)
                    <option value="{{ $sample->id }}">{{ $sample->lab_sample_id ?: $sample->external_id ?: "#{$sample->id}" }}</option>
                @endforeach
            </select>
            @error('sampleId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Linked to (parent record)</label>
            <select wire:model="parentRecordId" class="{{ $inputClass }}">
                <option value="">None</option>
                @foreach($parentCandidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->recordTypeLabel() }} (#{{ $candidate->id }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Performed by</label>
            <select wire:model="performedBy" class="{{ $inputClass }}">
                <option value="">Select…</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Date</label>
            <input type="date" wire:model="performedAt" class="{{ $inputClass }}">
            @error('performedAt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}">Note</label>
        <textarea wire:model="note" rows="2" class="{{ $inputClass }}"></textarea>
    </div>
</div>

@if(count($fieldSchema) > 0)
    <div class="bg-white shadow rounded-2xl p-6 space-y-4" x-data>
        <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($fieldSchema as $field)
                <div class="{{ in_array($field['type'], ['textarea']) ? 'md:col-span-3' : '' }}">
                    <label class="{{ $labelClass }}">{{ $field['label'] }}</label>

                    @switch($field['type'])
                        @case('textarea')
                            <textarea wire:model="detailsData.{{ $field['key'] }}" rows="2" class="{{ $inputClass }}"></textarea>
                            @break

                        @case('number')
                            <input type="number" step="any" wire:model="detailsData.{{ $field['key'] }}" class="{{ $inputClass }}">
                            @break

                        @case('select')
                            <select wire:model="detailsData.{{ $field['key'] }}" class="{{ $inputClass }}">
                                <option value="">Select…</option>
                                @foreach($field['options'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('multiselect')
                            <div class="flex flex-wrap gap-x-4 gap-y-2 border rounded-lg px-3 py-2">
                                @foreach($field['options'] as $key => $label)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" wire:model="detailsData.{{ $field['key'] }}" value="{{ $key }}" class="rounded border-gray-300">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('user_select')
                            <select wire:model="detailsData.{{ $field['key'] }}" class="{{ $inputClass }}">
                                <option value="">Select…</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @break

                        @default
                            <input wire:model="detailsData.{{ $field['key'] }}" class="{{ $inputClass }}">
                    @endswitch
                </div>
            @endforeach
        </div>
    </div>
@endif
