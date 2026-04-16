<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained('compounds')->onDelete('cascade');
            $table->string('kingdom', 255)->nullable();
            $table->string('superclass', 255)->nullable();
            $table->string('class', 255)->nullable();
            $table->string('subclass', 255)->nullable();
            $table->string('direct_parent', 255)->nullable();
            $table->string('alternative_parents', 255)->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();
            $table->unique('compound_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};
