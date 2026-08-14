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
        Schema::create('option_lists', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_nested')->default(false);
            // For a nested list (e.g. sample subgroups), each value can be scoped to one or more
            // values in THIS list via option_value_scopes — not a single fixed parent, since e.g.
            // "meat_products" is a sensible subgroup under both "food" and "animal".
            $table->foreignId('parent_list_id')->nullable()->constrained('option_lists')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_list_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['option_list_id', 'key']);
        });

        // Many-to-many: which parent-list value(s) a nested value is relevant under, e.g. the
        // "meat_products" sample-subgroup value is scoped to both the "food" and "animal" values
        // of the sample_groups list.
        Schema::create('option_value_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->foreignId('scope_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['option_value_id', 'scope_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_value_scopes');
        Schema::dropIfExists('option_values');
        Schema::dropIfExists('option_lists');
    }
};
