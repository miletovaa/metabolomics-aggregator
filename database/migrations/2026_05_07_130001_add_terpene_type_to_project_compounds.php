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
            $table->string('terpene_type')->nullable()->after('is_terpene');
        });

        // Backfill terpene_type for already-mapped terpene compounds.
        // is_alcohol = molecular_formula contains 'O'
        DB::statement("
            UPDATE project_compounds pc
            SET terpene_type = (
                SELECT
                    CASE
                        WHEN c.molecular_formula IS NULL THEN NULL
                        WHEN c.molecular_formula LIKE '%O%' THEN
                            CASE (regexp_match(c.molecular_formula, 'C(\d+)'))[1]::int
                                WHEN 10 THEN 'Monoterpenol'
                                WHEN 15 THEN 'Sesquiterpenol'
                                WHEN 20 THEN 'Diterpenol'
                                ELSE 'Other'
                            END
                        ELSE
                            CASE (regexp_match(c.molecular_formula, 'C(\d+)'))[1]::int
                                WHEN 10 THEN 'Monoterpene'
                                WHEN 15 THEN 'Sesquiterpene'
                                WHEN 20 THEN 'Diterpene'
                                ELSE 'Other'
                            END
                    END
                FROM compounds c
                WHERE c.id = pc.compound_id
            )
            WHERE pc.is_terpene = TRUE
        ");
    }

    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropColumn('terpene_type');
        });
    }
};
