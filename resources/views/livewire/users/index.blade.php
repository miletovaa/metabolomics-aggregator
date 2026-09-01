<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- Toast notification --}}
    @if($successMessage || $errorMessage)
        <div
            x-data="{ visible: true }"
            x-init="setTimeout(() => { visible = false; $wire.dismissNotification() }, 3500)"
            x-show="visible"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium
                {{ $errorMessage ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-green-50 text-green-800 border border-green-200' }}"
        >
            {{ $errorMessage ?? $successMessage }}
            <button wire:click="dismissNotification" class="ml-1 opacity-60 hover:opacity-100">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Users</h1>
            <p class="text-sm text-gray-500 mt-1">Manage accounts, roles, and per-resource permissions.</p>
        </div>
        @if($canEdit)
            <a href="{{ route('users.create') }}" wire:navigate class="bg-black text-white px-4 py-2 rounded-lg text-sm">
                + Add User
            </a>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-700">
                    <th class="p-3">ID</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Username</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Role</th>
                    <th class="p-3">Permissions</th>
                    <th class="p-3 w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr
                        wire:key="user-{{ $user->id }}"
                        @if($canEdit) x-on:click="Livewire.navigate('{{ route('users.edit', $user) }}')" @endif
                        class="hover:bg-gray-50 {{ $canEdit ? 'cursor-pointer' : '' }}"
                    >
                        <td class="p-3 text-gray-500">{{ $user->id }}</td>
                        <td class="p-3 font-medium text-gray-900">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="text-xs text-gray-400">(you)</span>
                            @endif
                            @if($user->password === null)
                                <span class="text-xs text-amber-600">— awaiting first sign-in</span>
                            @endif
                        </td>
                        <td class="p-3 text-gray-600">{{ $user->username ?: '—' }}</td>
                        <td class="p-3 text-gray-600">{{ $user->email ?: '—' }}</td>
                        <td class="p-3">
                            <span class="text-xs font-medium rounded-full px-2.5 py-1 {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ \App\Models\User::ROLES[$user->role] ?? $user->role }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-500 text-xs">
                            @if($user->role === 'admin')
                                All (admin)
                            @elseif(empty($user->permissions))
                                Own records only
                            @else
                                {{ count($user->permissions) }} granted
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            @if($canDelete)
                                <button
                                    x-on:click.stop
                                    wire:click="deleteUser({{ $user->id }})"
                                    wire:confirm="Delete \"{{ $user->name }}\"? This cannot be undone."
                                    class="text-gray-400 hover:text-red-600"
                                    title="{{ $user->isAdmin() ? 'Admin accounts cannot be deleted' : ($user->id === auth()->id() ? 'You cannot delete your own account' : 'Delete user') }}"
                                    @disabled($user->id === auth()->id() || $user->isAdmin())
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No users yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
