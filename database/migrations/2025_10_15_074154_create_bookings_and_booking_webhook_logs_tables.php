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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Collegamento alla tua location (ristorante interno al tuo gestionale)
            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Parametri della chiamata
            $table->string('restaurant_id')->nullable()->index(); // idRistorante di Prenota-Web
            $table->date('date_from')->index();
            $table->date('date_to')->index();
            $table->string('api_version', 16)->default('2');

            // Request/Response
            $table->string('request_url', 1024);
            $table->unsignedInteger('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->string('error_message', 2000)->nullable();
            $table->json('response_json')->nullable();

            // Metadati utili
            $table->unsignedInteger('num_reservations')->default(0);
            $table->timestamp('executed_at')->useCurrent();

            $table->timestamps();

            // Ricerca rapida per data e location
            $table->index(['location_id', 'executed_at']);
        });

        /**
         * Log degli INVII al webhook (una riga per prenotazione/versione inviata)
         * Serve per idempotenza: eviti invii doppi della stessa "versione" (stesso last_modified)
         */
        Schema::create('booking_webhook_logs', function (Blueprint $table) {
            $table->id();

            // A quale CHIAMATA api si riferisce questo invio
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            // Location per idempotenza cross-ristorante
            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Identità della prenotazione sul provider + versione
            $table->unsignedBigInteger('id_prenotazione')->index();
            $table->string('last_modified', 64)->nullable()->index(); // dataOraUltimaModifica dal provider

            // Esito invio webhook
            $table->unsignedInteger('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->string('error_message', 2000)->nullable();

            $table->timestamps();

            // Idempotenza: stessa prenotazione + stessa versione per stessa location => una sola riga
            $table->unique(['location_id', 'id_prenotazione', 'last_modified'], 'uniq_loc_prenot_mod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings_and_booking_webhook_logs_tables');
    }
};
