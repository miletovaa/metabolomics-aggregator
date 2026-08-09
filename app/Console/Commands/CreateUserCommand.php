<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('user:create {username?} {name?} {password?}')]
#[Description('Create a new user account (prompts for any argument left out)')]
class CreateUserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = $this->argument('username') ?? $this->ask('Username');
        $name = $this->argument('name') ?? $this->ask('Name');
        $password = $this->argument('password') ?? $this->secret('Password');

        $validator = Validator::make(
            ['username' => $username, 'name' => $name, 'password' => $password],
            [
                'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($password),
        ]);

        $this->info("User \"{$user->username}\" created (id={$user->id}).");

        return self::SUCCESS;
    }
}
