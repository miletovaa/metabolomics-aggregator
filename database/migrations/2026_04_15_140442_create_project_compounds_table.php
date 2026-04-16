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
        Schema::create('project_compounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('compound_id')->nullable()->constrained('compounds')->nullOnDelete();
            $table->string('custom_name', 255)->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->decimal('mz', 20, 10)->nullable();
            $table->decimal('rt', 20, 10)->nullable();
            $table->boolean('is_mapped')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_compounds');
    }
};
