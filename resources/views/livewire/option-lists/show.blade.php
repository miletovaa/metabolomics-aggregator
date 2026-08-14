<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ $optionList->name }}</h1>
            @if($optionList->is_nested)
                <p class="text-sm text-gray-500 mt-1">Each value can apply to one or more of the parent list's values — check every one it makes sense for.</p>
            @endif
        </div>
        <a href="{{ route('option-lists.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Back to all lists</a>
    </div>

    @if($successMessage)
        <div
            x-data="{ visible: true }"
            x-init="setTimeout(() => { visible = false; $wire.dismissNotification() }, 4000)"
            x-show="visible"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium bg-green-50 text-green-800 border border-green-200"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $successMessage }}
        </div>
    @endif

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3 w-20"></th>
                    <th class="p-3">Label</th>
                    <th class="p-3">Key</th>
                    @if($optionList->is_nested)
                        @foreach($parentValues as $parent)
                            <th class="p-3 text-center whitespace-nowrap">{{ $parent->label }}</th>
                        @endforeach
                    @endif
                    <th class="p-3 w-40"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($values as $value)
                    <tr wire:key="value-{{ $value->id }}">
                        <td class="p-3">
                            <div class="flex items-center gap-1">
                                <button wire:click="moveValue({{ $value->id }}, -1)" class="text-gray-400 hover:text-gray-700" title="Move up">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button wire:click="moveValue({{ $value->id }}, 1)" class="text-gray-400 hover:text-gray-700" title="Move down">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                        </td>

                        @if($editingValueId === $value->id)
                            <td class="p-3" colspan="2">
                                <input
                                    wire:model="editingLabel"
                                    wire:keydown.enter="saveEdit"
                                    type="text"
                                    class="w-full text-sm border px-2 py-1 rounded focus:outline-none focus:ring focus:ring-blue-200"
                                    autofocus
                                >
                                @if($editingLabelError)
                                    <p class="text-xs text-red-500 mt-1">{{ $editingLabelError }}</p>
                                @endif
                            </td>
                            @if($optionList->is_nested)
                                <td colspan="{{ count($parentValues) }}"></td>
                            @endif
                            <td class="p-3 text-right">
                                <button wire:click="saveEdit" class="text-green-600 hover:text-green-800 text-xs font-medium mr-2">Save</button>
                                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 text-xs">Cancel</button>
                            </td>
                        @else
                            <td class="p-3 text-gray-900">{{ $value->label }}</td>
                            <td class="p-3 text-gray-500 font-mono text-xs">{{ $value->key }}</td>
                            @if($optionList->is_nested)
                                @foreach($parentValues as $parent)
                                    <td class="p-3 text-center">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleScope({{ $value->id }}, {{ $parent->id }})"
                                            @checked(in_array($parent->id, $scopesByValue[$value->id] ?? []))
                                            class="rounded border-gray-300"
                                        >
                                    </td>
                                @endforeach
                            @endif
                            <td class="p-3 text-right">
                                <button wire:click="startEdit({{ $value->id }})" class="text-gray-500 hover:text-gray-800 text-xs font-medium mr-3">Edit</button>
                                <button
                                    wire:click="deleteValue({{ $value->id }})"
                                    wire:confirm="Delete \"{{ $value->label }}\"? It is currently used by {{ $this->usageCountFor($value) }} record(s)."
                                    class="text-red-500 hover:text-red-700 text-xs font-medium"
                                >
                                    Delete
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center text-gray-400">
                            No values yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 bg-white shadow rounded-xl p-4">
        <p class="text-xs text-gray-500 mb-2">Add a new value — the key is generated automatically from the label.</p>
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <input
                    wire:model="newLabel"
                    wire:keydown.enter="addValue"
                    type="text"
                    placeholder="e.g. Styrofoam box with ice"
                    class="w-full text-sm border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200"
                >
                @if($optionList->is_nested)
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                        @foreach($parentValues as $parent)
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-700">
                                <input type="checkbox" wire:model="newValueScopes" value="{{ $parent->id }}" class="rounded border-gray-300">
                                {{ $parent->label }}
                            </label>
                        @endforeach
                    </div>
                @endif
                @if($newLabelError)
                    <p class="text-xs text-red-500 mt-1">{{ $newLabelError }}</p>
                @endif
            </div>
            <button wire:click="addValue" class="bg-black text-white px-4 py-2 rounded-lg text-sm shrink-0">Add</button>
        </div>
    </div>
</div>
