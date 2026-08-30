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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('model')->nullable();
            
            $table->string('serial_number')->nullable();
            $table->string('image_url')->nullable();
            
            $table->bigInteger('criteria_brand_id')->nullable();
            $table->bigInteger('criteria_category_id')->nullable();
            $table->bigInteger('criteria_subcategory_id')->nullable();
            $table->bigInteger('criteria_provider_id')->nullable();

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
