<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Converte i valori esistenti di stato da integer a varchar
     */
    public function up(): void
    {
        // Usa raw SQL per evitare problemi di type casting
        // Converti valori numerici in valori testuali
        DB::statement("UPDATE slope_bookings SET stato = 'Attiva' WHERE stato IN ('1', '2', '4', '5')");
        DB::statement("UPDATE slope_bookings SET stato = 'Cancellata' WHERE stato = '3'");
        
        // Imposta 'Attiva' per tutti i valori NULL
        DB::statement("UPDATE slope_bookings SET stato = 'Attiva' WHERE stato IS NULL");
        
        // Gestisci eventuali valori già corretti (non fare nulla, sono già ok)
        // Gestisci valori non mappati (imposta come Attiva)
        DB::statement("UPDATE slope_bookings SET stato = 'Attiva' WHERE stato NOT IN ('Attiva', 'Modificata', 'Cancellata')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mappa inversa (approssimativa)
        $reverseMap = [
            'Attiva' => 1,
            'Modificata' => 1,
            'Cancellata' => 3,
        ];

        foreach ($reverseMap as $oldValue => $newValue) {
            DB::table('slope_bookings')
                ->where('stato', $oldValue)
                ->update(['stato' => $newValue]);
        }
    }
};
