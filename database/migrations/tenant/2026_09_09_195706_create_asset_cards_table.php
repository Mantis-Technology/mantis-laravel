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
        Schema::create('asset_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_card_template_id');
            $table->integer('version');

            $table->foreign(['asset_card_template_id', 'version'])
                ->references(['asset_card_template_id', 'version'])
                ->on('card_template_versions');
                
            $table->string('code')->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_cards');
    }
};
