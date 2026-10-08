<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-16: expected attention times (response and resolution) per
     * maintenance category, type and attention level. Null columns act as
     * wildcards, so a rule can apply to a category, to any case of a type,
     * etc. The most specific active rule wins when a case is evaluated.
     */
    public function up(): void
    {
        Schema::create('service_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_category_id')
                ->nullable()
                ->constrained('maintenance_categories')
                ->nullOnDelete();

            $table->string('maintenance_type')->nullable();
            $table->string('priority')->nullable();

            $table->unsignedInteger('response_hours');
            $table->unsignedInteger('resolution_hours');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['maintenance_category_id', 'maintenance_type', 'priority'],
                'service_levels_match_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_levels');
    }
};
