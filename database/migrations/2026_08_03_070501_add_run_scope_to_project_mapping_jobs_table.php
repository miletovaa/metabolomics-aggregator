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
        Schema::table('project_mapping_jobs', function (Blueprint $table) {
            $table->foreignId('experiment_id')->nullable()->after('project_id')->constrained('experiments')->nullOnDelete();
            $table->foreignId('sample_id')->nullable()->after('experiment_id')->constrained('samples')->nullOnDelete();
            $table->string('record_type', 50)->nullable()->after('sample_id');
            $table->foreignId('performed_by')->nullable()->after('record_type')->constrained('users')->nullOnDelete();
            $table->date('performed_at')->nullable()->after('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_mapping_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('sample_id');
            $table->dropConstrainedForeignId('experiment_id');
            $table->dropColumn(['record_type', 'performed_at']);
        });
    }
};
