<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();

            // Técnico responsable y quien hizo la asignación.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            // Periodo de la responsabilidad: ended_at nulo = asignación vigente.
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();

            $table->string('note', 500)->nullable();

            $table->index(['ticket_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};
