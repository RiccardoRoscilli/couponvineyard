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
        Schema::create('slope_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->uuid('slope_booking_id'); // UUID from Slope API
            
            // Campi per webhook (simili a Prenota-Web)
            $table->date('data'); // arrival date
            $table->time('ora')->nullable(); // check-in time se disponibile
            $table->string('cliente'); // nome completo cliente
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('lingua', 10)->nullable();
            $table->string('newsletter', 1)->nullable(); // 'S' o 'N'
            $table->text('note_int')->nullable();
            $table->integer('stato')->nullable(); // mapping da fare
            $table->integer('situazione')->nullable(); // mapping da fare
            
            // Campi aggiuntivi utili
            $table->date('departure_date')->nullable();
            $table->integer('adults')->default(0);
            $table->integer('children')->default(0);
            $table->boolean('is_canceled')->default(false);
            $table->timestamp('last_update_date')->nullable();
            
            // Sync tracking
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->unique(['location_id', 'slope_booking_id']);
            $table->index(['location_id', 'data']);
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slope_bookings');
    }
};
