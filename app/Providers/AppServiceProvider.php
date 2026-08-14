<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        // Open to every logged-in user for now — not gated on the manage_option_lists
        // permission yet. Revisit once real per-user access control is needed here.
        Gate::define('manage-option-lists', fn (User $user) => true);
    }
}
