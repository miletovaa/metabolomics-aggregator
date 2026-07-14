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
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('lab_sample_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('matrix_group')->nullable();
            $table->string('sample_group', 50);
            $table->string('sample_subgroup', 50)->nullable();
            $table->date('date_received')->nullable();
            $table->string('storage_condition', 50)->nullable();
            $table->json('storage_condition_details')->nullable();
            $table->foreignId('responsible_analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->json('purpose_of_analysis')->nullable();
            $table->json('planned_analysis')->nullable();
            $table->string('sample_type', 50)->nullable();
            $table->json('type_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
