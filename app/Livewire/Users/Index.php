<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('users', 'view'), 403);

        $this->successMessage = session('success');
    }

    public function deleteUser(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('users', 'delete'), 403);

        if ($id === Auth::id()) {
            $this->errorMessage = 'You cannot delete your own account.';

            return;
        }

        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            $this->errorMessage = 'Admin accounts cannot be deleted.';

            return;
        }

        $name = $user->name;
        $user->delete();

        ActivityLogger::log('delete_user', "Deleted account \"{$name}\".", null, $name, ['user_id' => $id], Auth::id());

        $this->successMessage = 'User deleted.';
        $this->errorMessage = null;
    }

    public function dismissNotification(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.users.index', [
            'users' => User::orderBy('id')->get(),
            'canEdit' => Auth::user()->hasPermission('users', 'edit'),
            'canDelete' => Auth::user()->hasPermission('users', 'delete'),
        ])->layout('layouts.app');
    }
}
