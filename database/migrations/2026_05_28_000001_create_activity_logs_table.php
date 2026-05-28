<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');       // create_project, edit_project, etc.
            $table->string('subject_type')->nullable();  // e.g. App\Models\Project
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();  // human-readable label
            $table->text('details')->nullable();         // free-text description
            $table->json('metadata')->nullable();        // structured extra data
            $table->timestamps();

            $table->index(['event_type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
