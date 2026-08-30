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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tax_id')->nullable()->after('name');
            $table->string('contact_email')->nullable()->after('tax_id');
            $table->string('phone')->nullable()->after('contact_email');
            $table->string('contact_name')->nullable()->after('phone');
            $table->string('address')->nullable()->after('contact_name');
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('country')->nullable()->after('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'tax_id',
                'contact_email',
                'phone',
                'contact_name',
                'address',
                'city',
                'province',
                'postal_code',
                'country',
            ]);
        });
    }
};
