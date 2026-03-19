<?php

/**
 * Script per debuggare l'aggiornamento delle date di scadenza
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Closure;
use Carbon\Carbon;

echo "=== DEBUG AGGIORNAMENTO DATE SCADENZA ===\n\n";

// Conta le prenotazioni "In Attesa"
$totalReservations = Reservation::where('status', 'In Attesa')->count();
echo "Totale prenotazioni 'In Attesa': {$totalReservations}\n\n";

// Prendi un campione di 5 prenotazioni
$reservations = Reservation::with('location')
    ->where('status', 'In Attesa')
    ->limit(5)
    ->get();

echo "--- CAMPIONE DI 5 PRENOTAZIONI ---\n\n";

foreach ($reservations as $reservation) {
    echo "ID: {$reservation->id}\n";
    echo "Codice: {$reservation->coupon_code}\n";
    echo "Location: {$reservation->location->name} (ID: {$reservation->location_id})\n";
    echo "Data fattura: {$reservation->data_fattura}\n";
    echo "Data scadenza attuale: {$reservation->data_scadenza}\n";
    
    // Calcola la data di scadenza base
    $dataFattura = Carbon::parse($reservation->data_fattura);
    $baseExpirationDate = clone $dataFattura;
    $baseExpirationDate->addMonths(6);
    
    echo "Data scadenza base (data_fattura + 6 mesi): {$baseExpirationDate->format('Y-m-d')}\n";
    
    // Recupera le chiusure
    $closurePeriods = Closure::where('location_id', $reservation->location_id)->get();
    echo "Numero chiusure per questa location: {$closurePeriods->count()}\n";
    
    if ($closurePeriods->count() > 0) {
        echo "Chiusure:\n";
        $totalClosureDays = 0;
        
        foreach ($closurePeriods as $period) {
            $closureStart = Carbon::parse($period->start_date);
            $closureEnd = Carbon::parse($period->end_date);
            
            echo "  - Dal {$period->start_date} al {$period->end_date}";
            
            // Verifica se cade nel periodo di validità
            if ($closureStart->between($dataFattura, $baseExpirationDate) || 
                $closureEnd->between($dataFattura, $baseExpirationDate) ||
                ($closureStart->lte($dataFattura) && $closureEnd->gte($baseExpirationDate))) {
                
                $closureDays = $closureStart->diffInDays($closureEnd) + 1;
                $totalClosureDays += $closureDays;
                echo " → CONTA ({$closureDays} giorni)\n";
            } else {
                echo " → NON CONTA (fuori dal periodo)\n";
            }
        }
        
        echo "Totale giorni di chiusura da aggiungere: {$totalClosureDays}\n";
        
        $newExpirationDate = clone $dataFattura;
        $newExpirationDate->addMonths(6)->addDays($totalClosureDays);
        echo "Data scadenza calcolata: {$newExpirationDate->format('Y-m-d')}\n";
        
        if ($reservation->data_scadenza == $newExpirationDate->format('Y-m-d')) {
            echo "✅ La data è GIÀ CORRETTA\n";
        } else {
            echo "⚠️  La data DOVREBBE essere aggiornata a: {$newExpirationDate->format('Y-m-d')}\n";
        }
    } else {
        echo "Nessuna chiusura configurata\n";
        if ($reservation->data_scadenza == $baseExpirationDate->format('Y-m-d')) {
            echo "✅ La data è GIÀ CORRETTA\n";
        } else {
            echo "⚠️  La data DOVREBBE essere: {$baseExpirationDate->format('Y-m-d')}\n";
        }
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

echo "=== DEBUG COMPLETATO ===\n";
