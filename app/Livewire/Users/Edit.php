<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'user';
    public array $permissions = [];

    public function mount(User $user): void
    {
        abort_unless(Auth::user()->hasPermission('users', 'edit'), 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->username = (string) $user->username;
        $this->email = (string) $user->email;
        $this->role = $user->role ?? 'user';
        $this->permissions = $user->permissions ?? [];
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username,' . $this->user->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:' . implode(',', array_keys(User::ROLES))],
        ]);

        if ($this->user->id === Auth::id() && $this->user->role === 'admin' && $this->role !== 'admin') {
            $this->addError('role', 'You cannot remove your own admin role.');

            return;
        }

        $this->user->fill([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email ?: null,
            'role' => $this->role,
            'permissions' => $this->permissions,
        ]);

        // Diffed before the password is touched — a hash must never end up in the activity log.
        $changes = ActivityLogger::diff($this->user);

        $passwordChanged = $this->password !== '';
        if ($passwordChanged) {
            $this->user->password = Hash::make($this->password);
        }

        $this->user->save();

        if ($changes) {
            ActivityLogger::log('edit_user', "Edited account \"{$this->user->name}\".", $this->user, $this->user->name, $changes, Auth::id());
        }

        if ($passwordChanged) {
            ActivityLogger::log('change_password', "Changed password for \"{$this->user->name}\".", $this->user, $this->user->name, [], Auth::id());
        }

        session()->flash('success', 'User updated.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.edit')->layout('layouts.app');
    }
}
