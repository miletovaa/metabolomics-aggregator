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
        Schema::create('compound_biomarkers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained('compounds')->onDelete('cascade');
            $table->foreignId('biomarker_id')->constrained('biomarkers')->onDelete('cascade');
            $table->text('reference')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compound_biomarkers');
    }
};
