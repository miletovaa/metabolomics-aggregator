@php
    $inputClass = 'w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200 text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Collection event</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelClass }}">Date of sampling</label>
            <input type="date" wire:model="dateOfSampling" class="{{ $inputClass }}">
            @error('dateOfSampling') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Country of sampling</label>
            <input wire:model="countryOfSampling" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">Place of sampling</label>
            <input wire:model="placeOfSampling" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">GERK</label>
            <input wire:model="gerk" class="{{ $inputClass }}" placeholder="Land parcel code, if applicable">
        </div>
        <div>
            <label class="{{ $labelClass }}">GPS latitude</label>
            <input wire:model="gpsLat" class="{{ $inputClass }}" placeholder="e.g. 46.048">
            @error('gpsLat') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">GPS longitude</label>
            <input wire:model="gpsLon" class="{{ $inputClass }}" placeholder="e.g. 14.506">
            @error('gpsLon') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Altitude (m)</label>
            <input wire:model="altitude" class="{{ $inputClass }}">
            @error('altitude') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Sampling method</label>
            <select wire:model="samplingMethod" class="{{ $inputClass }}">
                <option value="">Select…</option>
                @foreach($samplingMethods as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Packaging</label>
            <select wire:model="packaging" class="{{ $inputClass }}">
                <option value="">Select…</option>
                @foreach($packagingOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Collector</label>
            <input wire:model="collector" class="{{ $inputClass }}" placeholder="Name of the person who collected the sample">
        </div>
    </div>
</div>
