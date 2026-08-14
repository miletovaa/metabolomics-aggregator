<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:set-permission {username?} {permission?} {--revoke}')]
#[Description('Grant or revoke a permission for a user')]
class SetUserPermissionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = $this->argument('username') ?? $this->ask('Username');
        $permission = $this->argument('permission') ?? $this->choice('Permission', array_keys(User::PERMISSIONS), 0);

        if (! isset(User::PERMISSIONS[$permission])) {
            $this->error("Invalid permission \"{$permission}\". Valid permissions: " . implode(', ', array_keys(User::PERMISSIONS)) . '.');

            return self::FAILURE;
        }

        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->error("No user found with username \"{$username}\".");

            return self::FAILURE;
        }

        $permissions = $user->permissions ?? [];

        if ($this->option('revoke')) {
            $permissions = array_values(array_diff($permissions, [$permission]));
            $this->info("Revoked \"{$permission}\" from \"{$user->username}\".");
        } else {
            $permissions = array_values(array_unique([...$permissions, $permission]));
            $this->info("Granted \"{$permission}\" to \"{$user->username}\".");
        }

        $user->update(['permissions' => $permissions]);

        return self::SUCCESS;
    }
}
