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
        Schema::create('preventive_maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_category_id')->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('frequency_type');
            $table->unsignedInteger('interval')->default(1);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->foreignId('responsible_id')->constrained('users');
            $table->foreignId('created_by_id')->constrained('users');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('asset_card_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_maintenance_plans');
    }
};
