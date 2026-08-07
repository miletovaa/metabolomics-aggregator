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

        // Portable across Postgres/MySQL: PDO returns JSON columns as plain strings either way,
        // so the "contains 'terpen'" check is done in PHP rather than via a DB-specific cast/join.
        DB::table('project_compounds')
            ->whereNotNull('compound_id')
            ->orderBy('id')
            ->select('id', 'compound_id')
            ->chunkById(500, function ($rows) {
                $compoundIds = $rows->pluck('compound_id')->unique();

                $terpeneCompoundIds = DB::table('taxonomies')
                    ->whereIn('compound_id', $compoundIds)
                    ->whereNotNull('raw_json')
                    ->get(['compound_id', 'raw_json'])
                    ->filter(fn ($t) => str_contains(strtolower((string) $t->raw_json), 'terpen'))
                    ->pluck('compound_id');

                if ($terpeneCompoundIds->isNotEmpty()) {
                    DB::table('project_compounds')
                        ->whereIn('id', $rows->pluck('id'))
                        ->whereIn('compound_id', $terpeneCompoundIds)
                        ->update(['is_terpene' => true]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropColumn('is_terpene');
        });
    }
};
