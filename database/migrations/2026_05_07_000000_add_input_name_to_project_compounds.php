<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->string('input_name', 255)->nullable()->after('compound_id');
        });

        // Backfill: preserve whatever was in custom_name as the original input
        DB::statement('UPDATE project_compounds SET input_name = custom_name');
    }

    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropColumn('input_name');
        });
    }
};
