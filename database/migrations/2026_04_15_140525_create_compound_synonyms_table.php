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
        Schema::create('compound_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('compound_id')->constrained('compounds')->onDelete('cascade');
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->unique(['name', 'compound_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compound_synonyms');
    }
};
