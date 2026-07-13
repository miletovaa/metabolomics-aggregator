<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Dashboard') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('Welcome back, :name.', ['name' => auth()->user()->name]) }}</p>
    </div>

    {{-- Workspace --}}
    <section class="mb-10">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">{{ __('Workspace') }}</h2>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-dashboard-tile :href="route('projects.index')" title="Projects"
                description="Research projects and their compound observations."
                :meta="Str::plural('project', $projectsCount).': '.$projectsCount">
                <x-slot name="icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                    </svg>
                </x-slot>
            </x-dashboard-tile>

            <x-dashboard-tile :href="route('experiments.index')" title="Experiments"
                description="Conducted experiments linked to projects and samples.">
                <x-slot name="icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5.5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5.5 14.5m14.3.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5.5 14.5" />
                    </svg>
                </x-slot>
            </x-dashboard-tile>

            <x-dashboard-tile :href="route('samples.index')" title="Samples"
                description="Physical samples currently tracked in storage.">
                <x-slot name="icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </x-slot>
            </x-dashboard-tile>
        </div>
    </section>

    {{-- Catalog --}}
    <section>
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">{{ __('Catalog') }}</h2>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-dashboard-tile :href="route('compounds.index')" title="All Compounds"
                description="The shared reference database of known compounds."
                :meta="number_format($compoundsCount).' compounds'">
                <x-slot name="icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75v7.5m-16.5-7.5v7.5" />
                    </svg>
                </x-slot>
            </x-dashboard-tile>

            <x-dashboard-tile :href="route('samplings.index')" title="Samplings"
                description="Sampling technique definitions used to collect samples.">
                <x-slot name="icon">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                </x-slot>
            </x-dashboard-tile>
        </div>
    </section>
</div>
