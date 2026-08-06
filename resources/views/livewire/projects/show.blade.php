<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ $project->name }}</h1>
            @if($project->description)
                <p class="text-sm text-gray-600 mt-1 max-w-2xl">{{ $project->description }}</p>
            @endif
        </div>
        <a href="{{ route('projects.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">← Back to projects</a>
    </div>

    @include('livewire.projects.components.related-items')
</div>
