<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ontologies', function (Blueprint $table) {
            // name can be up to varchar(255) once parsed correctly,
            // but use text to be safe with edge cases
            $table->text('name')->change();
            // description can be several hundred chars from HMDB
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ontologies', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('description', 1000)->nullable()->change();
        });
    }
};