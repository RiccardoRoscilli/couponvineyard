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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('cognome_cliente')->nullable();
            $table->string('cognome_beneficiario')->nullable();
            $table->string('company_cliente')->nullable();
            $table->date('data_scadenza')->nullable();
            // Aggiungi altri campi qui se necessario
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            //
        });
    }
};
