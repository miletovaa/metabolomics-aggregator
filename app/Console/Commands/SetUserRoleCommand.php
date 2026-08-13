<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:set-role {username?} {role?}')]
#[Description('Set a user\'s role (user or admin)')]
class SetUserRoleCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = $this->argument('username') ?? $this->ask('Username');
        $role = $this->argument('role') ?? $this->choice('Role', array_keys(User::ROLES), 0);

        if (! isset(User::ROLES[$role])) {
            $this->error("Invalid role \"{$role}\". Valid roles: " . implode(', ', array_keys(User::ROLES)) . '.');

            return self::FAILURE;
        }

        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->error("No user found with username \"{$username}\".");

            return self::FAILURE;
        }

        $user->update(['role' => $role]);

        $this->info("User \"{$user->username}\" is now role \"{$role}\".");

        return self::SUCCESS;
    }
}
