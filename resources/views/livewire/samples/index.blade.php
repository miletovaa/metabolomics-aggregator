<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Samples</h1>
            <p class="text-sm text-gray-500 mt-1">Physical samples currently tracked in storage.</p>
        </div>
    </div>

    <x-empty-state title="No samples yet" cta="Add Sample"
        description="Samples will show up here once the lab starts logging physical specimens, each linked to the sampling technique used to collect it.">
        <x-slot name="icon">
            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>
        </x-slot>
    </x-empty-state>
</div>
