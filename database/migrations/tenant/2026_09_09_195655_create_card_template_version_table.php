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
        Schema::create('card_template_version', function (Blueprint $table) {

            $table->foreignId('asset_card_template_id')->constrained('asset_card_templates')->onDelete('cascade');
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->primary(['asset_card_template_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_template_version');
    }
};
