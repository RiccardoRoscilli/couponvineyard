<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prenota_locations', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_id')->unique(); // es. "1", "2", ...
            $table->string('name');                    // es. "Villa Crespi"
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete(); // link alla tua locations (se/quando esiste)
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        // (Opzionale ma utile) aggiungiamo il collegamento nei log/bookings
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('prenota_location_id')->nullable()->after('location_id')->constrained('prenota_locations')->nullOnDelete();
            });
        }
        if (Schema::hasTable('booking_webhook_logs')) {
            Schema::table('booking_webhook_logs', function (Blueprint $table) {
                $table->foreignId('prenota_location_id')->nullable()->after('location_id')->constrained('prenota_locations')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenota_locations');
    }
};
