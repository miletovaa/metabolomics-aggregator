@props(['href', 'title', 'description', 'meta' => null])

<a href="{{ $href }}" wire:navigate
    class="group bg-white rounded-xl shadow p-6 flex flex-col gap-4 hover:shadow-md hover:-translate-y-0.5 transition duration-150 ease-in-out">
    <div class="flex items-start justify-between">
        <div class="w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-100 transition">
            {{ $icon }}
        </div>
        <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 transition mt-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
    </div>

    <div>
        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    </div>

    @if($meta)
        <div class="text-xs font-medium text-gray-400 tabular-nums">{{ $meta }}</div>
    @endif
</a>
