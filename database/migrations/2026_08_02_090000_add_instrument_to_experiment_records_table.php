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
        Schema::table('experiment_records', function (Blueprint $table) {
            $table->string('instrument')->nullable()->after('performed_at');
            $table->index(['record_type', 'instrument']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiment_records', function (Blueprint $table) {
            $table->dropIndex(['record_type', 'instrument']);
            $table->dropColumn('instrument');
        });
    }
};
