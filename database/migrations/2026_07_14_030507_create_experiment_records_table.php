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
        Schema::create('experiment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained('samples')->cascadeOnDelete();
            $table->foreignId('parent_record_id')->nullable()->constrained('experiment_records')->nullOnDelete();
            $table->string('record_type', 50);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('performed_at')->nullable();
            $table->text('note')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['experiment_id', 'record_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiment_records');
    }
};
