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
        Schema::create('asset_card_template_sections', function (Blueprint $table) {
            $table->ulid()->primary();

            $table->foreignId('asset_card_template_id')->constrained('asset_card_templates')->onDelete('cascade');
            $table->integer('version')->default(1);

            $table->foreign(['asset_card_template_id', 'version'])
                ->references(['asset_card_template_id', 'version'])
                ->on('card_template_version')
                ->onDelete('cascade');
            
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('order')->default(1);
            $table->integer('columns')->default(12);
            $table->json('fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_card_template_sections');
    }
};
