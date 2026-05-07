<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biomarkers', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('diseases', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
        });

        // ontologies was already widened in the previous migration,
        // but re-stating it here is harmless if you skipped that one.
        Schema::table('ontologies', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        foreach (['biomarkers', 'diseases', 'ontologies'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('name', 255)->change();
                $table->string('description', 1000)->nullable()->change();
            });
        }
    }
};