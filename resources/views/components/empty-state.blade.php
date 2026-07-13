@props(['title', 'description', 'cta' => null])

<div class="bg-white rounded-xl shadow p-16 flex flex-col items-center text-center">
    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mb-4">
        {{ $icon }}
    </div>

    <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
    <p class="mt-1 text-sm text-gray-500 max-w-sm">{{ $description }}</p>

    @if($cta)
        <button type="button" disabled
            class="mt-6 bg-black text-white px-4 py-2 rounded-lg text-sm opacity-40 cursor-not-allowed"
            title="Coming soon">
            {{ $cta }}
        </button>
    @endif
</div>
