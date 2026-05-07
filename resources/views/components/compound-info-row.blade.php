@props([
    'label',
    'value' => null,
    'mono' => false,
])

<div class="rounded-lg border bg-gray-50 p-3">
    <div class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
        {{ $label }}
    </div>

    <div class="{{ $mono ? 'font-mono text-xs break-all' : 'text-sm' }} text-gray-900">
        {{ filled($value) ? $value : '—' }}
    </div>
</div>