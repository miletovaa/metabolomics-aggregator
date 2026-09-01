<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'user';
    public array $permissions = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('users', 'edit'), 403);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            // Left blank, the account has no password yet — whoever first signs in as this
            // user sets their own password (see LoginForm::authenticate()).
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:' . implode(',', array_keys(User::ROLES))],
        ]);

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email ?: null,
            'password' => $this->password ? Hash::make($this->password) : null,
            'role' => $this->role,
            'permissions' => $this->permissions,
        ]);

        ActivityLogger::log(
            'create_user',
            "Created account \"{$user->name}\" (username: {$user->username}).",
            $user,
            $user->name,
            [],
            Auth::id(),
        );

        session()->flash('success', 'User created.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.create')->layout('layouts.app');
    }
}
