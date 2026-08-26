@php
    $headerFields = [
        'decision' => 'Decision',
        'mode' => 'Mode',
        'dilution_factor' => 'Dilution factor',
        'lod_ng_g' => 'LOD (ng/g)',
        'pct_below_lod' => '% < LOD',
    ];
    $standardFields = [
        'avg' => 'Avg (ng/g)',
        'rsd_pct' => 'RSD (%)',
        'reference_value' => 'Reference value (ng/g)',
        'uncertainty' => 'Uncertainty (ng/g)',
    ];
    $formatCell = function ($value) {
        if ($value === null) {
            return '—';
        }
        return is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 3), '0'), '.') : $value;
    };
@endphp

<div class="pt-2 border-t space-y-4">
    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Run QC — per element</div>
    <div class="overflow-x-auto">
        <table class="text-xs border-collapse w-full">
            <thead>
                <tr>
                    <th class="p-2 text-left text-gray-500 border-b whitespace-nowrap"></th>
                    @foreach($qc['elements'] as $symbol)
                        <th class="p-2 text-center text-gray-700 border-b font-medium">{{ $symbol }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($headerFields as $field => $label)
                    <tr>
                        <td class="p-2 text-gray-500 whitespace-nowrap">{{ $label }}</td>
                        @foreach($qc['elements'] as $symbol)
                            <td class="p-2 text-center text-gray-900">{{ $formatCell($qc['per_element'][$symbol][$field] ?? null) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(count($qc['standards']) > 0)
        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 pt-2">Reference standards</div>
        <div class="space-y-4">
            @foreach($qc['standards'] as $standard)
                @php($standardElements = collect($qc['elements'])->filter(fn ($s) => isset($standard['per_element'][$s]))->values())
                <div class="space-y-1">
                    <div class="text-xs font-semibold text-gray-700">{{ $standard['name'] }}</div>
                    <div class="overflow-x-auto">
                        <table class="text-xs border-collapse w-full">
                            <thead>
                                <tr>
                                    <th class="p-2 text-left text-gray-500 border-b whitespace-nowrap"></th>
                                    @foreach($standardElements as $symbol)
                                        <th class="p-2 text-center text-gray-700 border-b font-medium">{{ $symbol }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($standardFields as $field => $label)
                                    <tr>
                                        <td class="p-2 text-gray-500 whitespace-nowrap">{{ $label }}</td>
                                        @foreach($standardElements as $symbol)
                                            <td class="p-2 text-center text-gray-900">{{ $formatCell($standard['per_element'][$symbol][$field] ?? null) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
