<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('preventive_maintenance_executions', function (Blueprint $table) {
            $table->date('original_scheduled_at')->nullable()->after('scheduled_at');
        });

        DB::table('preventive_maintenance_executions')
            ->whereNull('original_scheduled_at')
            ->update(['original_scheduled_at' => DB::raw('scheduled_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventive_maintenance_executions', function (Blueprint $table) {
            $table->dropColumn('original_scheduled_at');
        });
    }
};
