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
        // Crea la tabella se non esiste
        if (!Schema::hasTable('prenota_locations')) {
            Schema::create('prenota_locations', function (Blueprint $table) {
                $table->id();
                $table->string('restaurant_id')->unique(); // es. "1", "2", ...
                $table->string('name');
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete(); // link opzionale alla tua 'locations'
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // Seed (idempotente)
        $rows = [
            ['restaurant_id' => '1', 'name' => 'Villa Crespi'],
            ['restaurant_id' => '2', 'name' => 'Bistrot Novara'],
            ['restaurant_id' => '3', 'name' => 'Bistrot Torino'],
            ['restaurant_id' => '4', 'name' => 'Cafè Novara'],
            ['restaurant_id' => '5', 'name' => 'Laqua by the lake'],
            ['restaurant_id' => '6', 'name' => 'Laqua Countryside'],
            ['restaurant_id' => '7', 'name' => 'Villa Crespi Esterno'],
            ['restaurant_id' => '8', 'name' => 'Laqua vineyard'],
            ['restaurant_id' => '9', 'name' => 'Corsi'],
        ];

        foreach ($rows as $r) {
            DB::table('prenota_locations')->updateOrInsert(
                ['restaurant_id' => $r['restaurant_id']],
                ['name' => $r['name'], 'is_enabled' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('and_seed_prenota_locations');
    }
};
