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
        Schema::create('preventive_maintenance_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_maintenance_plan_id')->nullable()->constrained('preventive_maintenance_plans')->nullOnDelete();
            $table->foreignId('asset_card_id')->constrained();
            $table->foreignId('maintenance_category_id')->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->date('scheduled_at');
            $table->foreignId('assigned_to_id')->constrained('users');
            $table->string('status')->default('scheduled');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('execution_notes')->nullable();
            $table->text('findings')->nullable();
            $table->timestamps();

            $table->unique(['preventive_maintenance_plan_id', 'scheduled_at'], 'preventive_exec_plan_date_unique');
            $table->index(['assigned_to_id', 'status'], 'preventive_exec_assignee_status_idx');
            $table->index(['asset_card_id', 'scheduled_at'], 'preventive_exec_asset_date_idx');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_maintenance_executions');
    }
};
