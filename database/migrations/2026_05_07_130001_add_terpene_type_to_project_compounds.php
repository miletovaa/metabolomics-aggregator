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

        // Backfill terpene_type for already-mapped terpene compounds. Done in PHP (rather than a
        // Postgres-only regexp_match(...)::int expression) so this migration also runs on MySQL.
        // is_alcohol = molecular_formula contains 'O'; carbon count from the formula's "C<n>" part.
        DB::table('project_compounds')
            ->where('is_terpene', true)
            ->whereNotNull('compound_id')
            ->orderBy('id')
            ->select('id', 'compound_id')
            ->chunkById(500, function ($rows) {
                $compounds = DB::table('compounds')
                    ->whereIn('id', $rows->pluck('compound_id')->unique())
                    ->get(['id', 'molecular_formula'])
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $formula = $compounds->get($row->compound_id)?->molecular_formula;

                    if ($formula === null) {
                        continue;
                    }

                    $isAlcohol = str_contains($formula, 'O');
                    $carbonCount = preg_match('/C(\d+)/', $formula, $m) ? (int) $m[1] : null;

                    $terpeneType = match ($carbonCount) {
                        10 => $isAlcohol ? 'Monoterpenol' : 'Monoterpene',
                        15 => $isAlcohol ? 'Sesquiterpenol' : 'Sesquiterpene',
                        20 => $isAlcohol ? 'Diterpenol' : 'Diterpene',
                        default => 'Other',
                    };

                    DB::table('project_compounds')->where('id', $row->id)->update(['terpene_type' => $terpeneType]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropColumn('terpene_type');
        });
    }
};
