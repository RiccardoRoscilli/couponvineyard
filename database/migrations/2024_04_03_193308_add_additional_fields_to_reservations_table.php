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
            $table->string('nome_activity')->nullable();
            $table->text('description_activity')->nullable();
            $table->text('details_activity')->nullable();
            $table->text('note_activity')->nullable();
            $table->boolean('prenotare_activity')->default(false);
            $table->string('status')->nullable();
            $table->string('n_fattura')->nullable();
            $table->dateTime('data_fattura')->nullable();
            $table->string('nome_cliente')->nullable();
            $table->string('email_cliente')->nullable();
            $table->string('nome_beneficiario')->nullable();
            $table->string('email_beneficiario')->nullable();
            $table->string('telefono_beneficiario')->nullable();
            $table->text('note_beneficiario')->nullable();
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
