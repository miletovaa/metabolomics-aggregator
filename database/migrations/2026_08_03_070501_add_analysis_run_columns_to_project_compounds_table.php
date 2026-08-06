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
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->foreignId('experiment_id')->nullable()->after('compound_id')->constrained('experiments')->nullOnDelete();
            $table->foreignId('sample_id')->nullable()->after('experiment_id')->constrained('samples')->nullOnDelete();
            $table->string('record_type', 50)->nullable()->after('sample_id');
            $table->foreignId('performed_by')->nullable()->after('record_type')->constrained('users')->nullOnDelete();
            $table->date('performed_at')->nullable()->after('performed_by');
            $table->foreignId('parent_record_id')->nullable()->after('performed_at')->constrained('experiment_records')->nullOnDelete();
            $table->decimal('value', 20, 10)->nullable()->after('parent_record_id');
            $table->string('unit', 50)->nullable()->after('value');
            $table->decimal('stdev', 20, 10)->nullable()->after('unit');

            $table->index(
                ['experiment_id', 'sample_id', 'record_type', 'performed_by', 'performed_at'],
                'project_compounds_run_scope_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_compounds', function (Blueprint $table) {
            $table->dropIndex('project_compounds_run_scope_index');
            $table->dropConstrainedForeignId('parent_record_id');
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('sample_id');
            $table->dropConstrainedForeignId('experiment_id');
            $table->dropColumn(['record_type', 'performed_at', 'value', 'unit', 'stdev']);
        });
    }
};
