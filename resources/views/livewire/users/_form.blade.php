@php
    $inputClass = 'w-full border px-3 py-2 rounded-lg focus:outline-none focus:ring focus:ring-blue-200 text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

{{-- Account --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Account</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="{{ $labelClass }}">Name <span class="text-red-500">*</span></label>
            <input wire:model="name" class="{{ $inputClass }}">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Username <span class="text-red-500">*</span></label>
            <input wire:model="username" class="{{ $inputClass }}">
            @error('username') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Email</label>
            <input type="email" wire:model="email" class="{{ $inputClass }}">
            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Password @if(isset($isEdit) && $isEdit) <span class="text-gray-400 font-normal">(leave blank to keep current)</span> @else <span class="text-red-500">*</span> @endif</label>
            <input type="password" wire:model="password" class="{{ $inputClass }}" autocomplete="new-password">
            @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">Role</label>
            <select wire:model.live="role" class="{{ $inputClass }}">
                @foreach(\App\Models\User::ROLES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

{{-- Permissions --}}
<div class="bg-white shadow rounded-2xl p-6 space-y-4">
    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Permissions</h2>
    <p class="text-xs text-gray-500">
        Everyone can always view/edit/delete their own (or assigned) projects, samples, samplings, and experiments.
        These toggles extend access to <strong>everyone else's</strong> records for those four, and gate the resource
        outright for Compounds, Predefined Values, Activity Log, and Users. Admins have every permission automatically,
        regardless of these toggles.
    </p>

    @if($role === 'admin')
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            This user is an Admin — the toggles below have no effect since admins already have every permission.
        </p>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-600 border-b">
                    <th class="py-2 pr-4">Resource</th>
                    @foreach(\App\Models\User::PERMISSION_ACTIONS as $actionKey => $actionLabel)
                        <th class="py-2 px-4 text-center whitespace-nowrap">{{ $actionLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(\App\Models\User::PERMISSION_RESOURCES as $resourceKey => $resourceLabel)
                    <tr>
                        <td class="py-2 pr-4 text-gray-900">{{ $resourceLabel }}</td>
                        @foreach(\App\Models\User::PERMISSION_ACTIONS as $actionKey => $actionLabel)
                            <td class="py-2 px-4 text-center">
                                <input
                                    type="checkbox"
                                    wire:model="permissions"
                                    value="{{ $resourceKey }}.{{ $actionKey }}"
                                    class="rounded border-gray-300"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
