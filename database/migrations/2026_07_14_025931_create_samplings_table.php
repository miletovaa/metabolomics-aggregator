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
        Schema::create('samplings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->unique()->constrained('samples')->cascadeOnDelete();
            $table->date('date_of_sampling')->nullable();
            $table->string('country_of_sampling')->nullable();
            $table->string('place_of_sampling')->nullable();
            $table->string('gerk')->nullable();
            $table->decimal('gps_lat', 10, 6)->nullable();
            $table->decimal('gps_lon', 10, 6)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->string('sampling_method', 50)->nullable();
            $table->string('packaging', 50)->nullable();
            $table->string('collector')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samplings');
    }
};
