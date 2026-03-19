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
        Schema::table('slope_bookings', function (Blueprint $table) {
            // Cambia stato da integer a varchar
            $table->string('stato', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slope_bookings', function (Blueprint $table) {
            // Ripristina stato a integer
            $table->integer('stato')->nullable()->change();
        });
    }
};
