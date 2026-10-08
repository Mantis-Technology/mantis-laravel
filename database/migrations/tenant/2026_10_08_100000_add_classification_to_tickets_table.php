<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-13: a reported case is classified with the maintenance category
     * (failure class), the maintenance type and the attention level, plus
     * who performed the classification and when.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('maintenance_category_id')
                ->nullable()
                ->after('asset_card_id')
                ->constrained('maintenance_categories')
                ->nullOnDelete();

            $table->string('maintenance_type')->nullable()->after('status');
            $table->string('priority')->nullable()->after('maintenance_type');

            $table->foreignId('categorized_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('categorized_at')->nullable()->after('categorized_by');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['maintenance_category_id']);
            $table->dropForeign(['categorized_by']);
            $table->dropColumn([
                'maintenance_category_id',
                'maintenance_type',
                'priority',
                'categorized_by',
                'categorized_at',
            ]);
        });
    }
};
