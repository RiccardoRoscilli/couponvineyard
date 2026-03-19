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
        Schema::table('locations', function (Blueprint $table) {
            $table->text('slope_bearer_token')->nullable()->after('prenota_web_restaurant_id');
            $table->boolean('slope_enabled')->default(false)->after('slope_bearer_token');
            $table->index('slope_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['slope_enabled']);
            $table->dropColumn(['slope_bearer_token', 'slope_enabled']);
        });
    }
};
