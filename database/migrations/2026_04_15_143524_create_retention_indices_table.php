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
        Schema::create('retention_indices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained('compounds');
            $table->decimal('value', 20, 10);
            $table->string('column_type', 50);
            $table->boolean('is_polar')->default(false);
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
        Schema::dropIfExists('retention_indices');
    }
};
