@if($showConflictModal && !empty($conflictGroups))
    @php
        $totalConflicts = count($conflictGroups);
        $isName         = $conflictType === 'name';
    @endphp

    <div
        x-data
        x-show="$wire.showConflictModal"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.closeConflictModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
        <div class="absolute inset-0" wire:click="closeConflictModal"></div>

        <div
            class="relative w-full max-w-2xl max-h-[85vh] flex flex-col rounded-2xl bg-white shadow-xl"
            @click.stop
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            {{-- Header --}}
            <div class="rounded-t-2xl px-6 py-4 {{ $isName ? 'bg-red-600' : 'bg-amber-500' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-white">
                            You have {{ $totalConflicts }} {{ Str::plural('conflict', $totalConflicts) }}:
                        </h2>
                        <p class="text-white/70 text-sm mt-0.5">
                            @if($isName)
                                These rows share the same name within this project.
                            @else
                                These rows mapped to the same compound identifier.
                            @endif
                        </p>
                    </div>
                    <button
                        wire:click="closeConflictModal"
                        class="text-white/70 hover:text-white text-xl leading-none ml-4"
                    >✕</button>
                </div>
            </div>

            {{-- Conflict groups --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">
                @foreach($conflictGroups as $gi => $group)
                    @php
                        $groupIds = array_column($group['rows'], 'id');
                        $firstRow = $group['rows'][0];
                        $label    = $firstRow['input_name'] ?? $firstRow['custom_name'] ?? "Group " . ($gi + 1);
                    @endphp

                    <div
                        x-data="{
                            selectedId: {{ $firstRow['id'] }},
                            editing: false,
                            editName: '',
                            editRt: '',
                            editMz: '',
                            rows: {{ Js::from($group['rows']) }},
                            startEdit() {
                                const row = this.rows.find(r => r.id === this.selectedId) ?? this.rows[0];
                                this.editName = row.input_name ?? row.custom_name ?? '';
                                this.editRt   = row.rt !== null && row.rt !== undefined ? parseFloat(row.rt).toFixed(2) : '';
                                this.editMz   = row.mz !== null && row.mz !== undefined ? parseFloat(row.mz).toFixed(2) : '';
                                this.editing  = true;
                            },
                            cancelEdit() {
                                this.editing = false;
                                this.editName = '';
                                this.editRt   = '';
                                this.editMz   = '';
                            }
                        }"
                    >
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Conflict {{ $gi + 1 }}: &ldquo;{{ $label }}&rdquo;
                        </div>

                        {{-- Table --}}
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-left border-b border-gray-200">
                                    <tr class="text-gray-500 text-xs">
                                        <th class="px-4 py-2 font-medium">Name</th>
                                        <th class="px-4 py-2 font-medium">RI</th>
                                        <th class="px-4 py-2 font-medium">m/z</th>
                                        <th class="px-4 py-2 font-medium">Status</th>
                                        <th class="px-4 py-2 w-52"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach($group['rows'] as $row)
                                        {{-- Normal display row --}}
                                        <tr
                                            class="group/row transition-colors cursor-pointer"
                                            :class="{
                                                'bg-blue-50': selectedId === {{ $row['id'] }} && !editing,
                                                'bg-blue-50/50 ring-1 ring-inset ring-blue-200': selectedId === {{ $row['id'] }} && editing,
                                                'hover:bg-gray-50': selectedId !== {{ $row['id'] }}
                                            }"
                                            x-show="!editing || selectedId !== {{ $row['id'] }}"
                                            @click="selectedId = {{ $row['id'] }}"
                                        >
                                            <td class="px-4 py-2.5 font-medium text-gray-800">
                                                {{ $row['input_name'] ?? $row['custom_name'] ?? '—' }}
                                                @if(($row['custom_name'] ?? '') !== ($row['input_name'] ?? '') && !empty($row['custom_name']))
                                                    <span class="text-xs text-gray-400 ml-1">(custom: {{ $row['custom_name'] }})</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-500">
                                                {{ $row['rt'] !== null ? number_format((float)$row['rt'], 2) : '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-500">
                                                {{ $row['mz'] !== null ? number_format((float)$row['mz'], 2) : '—' }}
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @if($row['is_mapped'])
                                                    <span class="inline-block text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">mapped</span>
                                                @else
                                                    <span class="inline-block text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">raw</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button
                                                    class="opacity-0 group-hover/row:opacity-100 text-xs text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap transition-opacity"
                                                    @click.stop="$wire.keepConflictRow({{ $row['id'] }}, {{ json_encode(array_values(array_diff($groupIds, [$row['id']]))) }})"
                                                    title="Keep this row, delete the others"
                                                >
                                                    Select this one as correct one
                                                </button>
                                            </td>
                                        </tr>

                                        {{-- Inline edit row (replaces the selected row while editing) --}}
                                        <tr
                                            x-show="editing && selectedId === {{ $row['id'] }}"
                                            class="bg-blue-50"
                                        >
                                            <td class="px-4 py-2">
                                                <input
                                                    x-model="editName"
                                                    type="text"
                                                    placeholder="Name"
                                                    class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                                                >
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    x-model="editRt"
                                                    type="text"
                                                    placeholder="RI"
                                                    class="w-20 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                                                >
                                            </td>
                                            <td class="px-4 py-2">
                                                <input
                                                    x-model="editMz"
                                                    type="text"
                                                    placeholder="m/z"
                                                    class="w-20 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                                                >
                                            </td>
                                            <td></td>
                                            <td class="px-4 py-2 text-right">
                                                <div class="flex gap-2 justify-end">
                                                    <button
                                                        @click="$wire.saveConflictEdit(selectedId, editName, editRt, editMz); editing = false"
                                                        class="text-xs bg-black text-white rounded-lg px-3 py-1.5 hover:bg-gray-800"
                                                    >Save &amp; keep</button>
                                                    <button
                                                        @click="cancelEdit()"
                                                        class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1.5"
                                                    >Cancel</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Group action buttons --}}
                        <div class="flex gap-2 mt-3" x-show="!editing">
                            <button
                                @click="startEdit()"
                                class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 font-medium"
                            >
                                Input correct value
                            </button>
                            <button
                                @click="$wire.deleteConflictGroup({{ json_encode($groupIds) }})"
                                class="text-sm px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium"
                            >
                                {{ count($group['rows']) === 2 ? 'Delete both' : 'Delete all' }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="border-t px-6 py-4 flex justify-end rounded-b-2xl bg-gray-50">
                <button
                    wire:click="closeConflictModal"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
@endif
