<?php

/**
 * Esempio di utilizzo della funzionalità di check cancellazioni Slope
 * 
 * Questo script mostra come usare il nuovo metodo checkRecentCancellations
 * per verificare se prenotazioni recenti sono state cancellate in Slope.
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Location;
use App\Services\SlopeApiClient;
use App\Services\SlopeService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Slope Cancellation Check - Example\n";
echo "========================================\n\n";

// 1. Trova una location con Slope abilitato
$location = Location::where('slope_enabled', true)->first();

if (!$location) {
    echo "❌ Nessuna location con Slope abilitato trovata.\n";
    exit(1);
}

echo "📍 Location: {$location->name} (ID: {$location->id})\n\n";

// 2. Verifica che la location abbia un bearer token
if (!$location->slope_bearer_token) {
    echo "❌ La location non ha un bearer token configurato.\n";
    exit(1);
}

echo "✓ Bearer token configurato\n\n";

// 3. Mostra le prenotazioni recenti non cancellate
echo "📋 Prenotazioni recenti non cancellate:\n";
echo "----------------------------------------\n";

$recentBookings = \App\Models\SlopeBooking::where('location_id', $location->id)
    ->where('data', '>=', now()->subDays(15))
    ->where('stato', '!=', 'Cancellata')
    ->orderBy('data', 'desc')
    ->get();

if ($recentBookings->isEmpty()) {
    echo "Nessuna prenotazione recente non cancellata trovata.\n\n";
} else {
    foreach ($recentBookings as $booking) {
        echo sprintf(
            "- %s | %s | %s | %s\n",
            $booking->data->format('Y-m-d'),
            $booking->cliente,
            $booking->stato,
            substr($booking->slope_booking_id, 0, 8) . '...'
        );
    }
    echo "\nTotale: {$recentBookings->count()} prenotazioni\n\n";
}

// 4. Chiedi conferma prima di procedere
echo "Vuoi verificare se queste prenotazioni sono state cancellate in Slope? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'y') {
    echo "Operazione annullata.\n";
    exit(0);
}

echo "\n";

// 5. Crea il client API e il service
$apiClient = new SlopeApiClient($location->slope_bearer_token);
$service = new SlopeService($apiClient);

// 6. Esegui il check delle cancellazioni
echo "🔍 Verifica cancellazioni in corso...\n";
echo "----------------------------------------\n";

try {
    $stats = $service->checkRecentCancellations($location, 15);
    
    echo "\n✅ Verifica completata!\n\n";
    echo "📊 Statistiche:\n";
    echo "----------------------------------------\n";
    echo "Prenotazioni verificate: {$stats['checked']}\n";
    echo "Prenotazioni cancellate: {$stats['canceled']}\n";
    echo "Errori: " . count($stats['errors']) . "\n";
    
    if (!empty($stats['errors'])) {
        echo "\n❌ Errori riscontrati:\n";
        foreach ($stats['errors'] as $error) {
            echo "- {$error}\n";
        }
    }
    
    // 7. Mostra le prenotazioni appena cancellate
    if ($stats['canceled'] > 0) {
        echo "\n🚫 Prenotazioni appena marcate come cancellate:\n";
        echo "----------------------------------------\n";
        
        $canceledBookings = \App\Models\SlopeBooking::where('location_id', $location->id)
            ->where('stato', 'Cancellata')
            ->where('synced_at', '>=', now()->subMinutes(1))
            ->get();
        
        foreach ($canceledBookings as $booking) {
            echo sprintf(
                "- %s | %s | %s\n",
                $booking->data->format('Y-m-d'),
                $booking->cliente,
                substr($booking->slope_booking_id, 0, 8) . '...'
            );
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ Errore durante la verifica:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Esempio completato!\n";
echo "========================================\n";
