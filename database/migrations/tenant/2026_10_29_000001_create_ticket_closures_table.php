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
        Schema::create('ticket_closures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->unique()
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->text('work_performed');
            $table->string('result');
            $table->text('observations')->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_closures');
    }
};
