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
            $table->string('utente_mail')->nullable();
            $table->string('password_mail')->nullable();
            $table->string('telefono')->nullable();
            $table->string('logo')->nullable(); // Assicurati di avere spazio sufficiente per il percorso del file
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            //
        });
    }
};
