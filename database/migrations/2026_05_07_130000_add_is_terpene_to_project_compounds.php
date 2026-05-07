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
            $table->boolean('is_terpene')->default(false)->after('is_duplicate');
        });

        DB::statement("
            UPDATE project_compounds pc
            SET is_terpene = TRUE
            FROM taxonomies t
            WHERE pc.compound_id = t.compound_id
              AND LOWER(t.raw_json::text) LIKE '%terpen%'
        ");
    }

    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropColumn('is_terpene');
        });
    }
};
