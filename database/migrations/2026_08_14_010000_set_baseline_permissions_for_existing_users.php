<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Before this migration, Compounds and the predefined-values (Options) page were open to
     * every logged-in user, and the Activity Log had no gating at all. The new granular
     * permission system would otherwise silently lock existing non-admin users out of all
     * three the moment it deploys — grant them the equivalent baseline so nothing regresses.
     * Admins are untouched: they bypass every permission check already.
     */
    public function up(): void
    {
        $baseline = json_encode(['compounds.view', 'compounds.edit', 'options.view', 'options.edit', 'history.view']);

        DB::table('users')->where('role', '!=', 'admin')->update(['permissions' => $baseline]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', '!=', 'admin')->update(['permissions' => null]);
    }
};
