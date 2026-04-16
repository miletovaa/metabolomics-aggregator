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
        Schema::create('compounds', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_name', 255);
            $table->string('iupac_name', 255)->nullable();
            $table->string('molecular_formula', 255)->nullable();
            $table->text('smiles')->nullable();
            $table->text('inchi')->unique()->nullable();
            $table->string('inchikey', 30)->unique()->nullable();
            $table->string('pubchem_cid', 255)->unique()->nullable();
            $table->string('cas', 255)->nullable();
            $table->string('hmdb_id', 255)->unique()->nullable();
            $table->string('chebi_id', 255)->unique()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compounds');
    }
};
