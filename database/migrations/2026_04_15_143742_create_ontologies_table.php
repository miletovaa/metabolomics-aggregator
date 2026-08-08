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
        Schema::create('ontologies', function (Blueprint $table) {
            $table->id();
            // Not unique: this column is later widened to text() (raw HMDB values can run to
            // several thousand characters), and MySQL can't carry a unique index on TEXT/BLOB
            // columns. Deduplication is handled at the application level (Ontology::firstOrCreate).
            $table->string('name', 255);
            $table->string('type', 255)->nullable();
            $table->string('description', 1000)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ontologies');
    }
};
