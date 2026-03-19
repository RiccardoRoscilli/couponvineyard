<?php

/**
 * Script per rigenerare il PDF del voucher con la data corretta dal database
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

echo "=== RIGENERAZIONE VOUCHER CON DATA CORRETTA ===\n\n";

// Chiedi il codice voucher o l'ID della prenotazione
echo "Inserisci il codice voucher o l'ID della prenotazione: ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

// Cerca la prenotazione
$reservation = null;
if (is_numeric($input)) {
    $reservation = Reservation::find($input);
} else {
    $reservation = Reservation::where('coupon_code', $input)->first();
}

if (!$reservation) {
    echo "❌ Prenotazione non trovata!\n";
    exit(1);
}

echo "\n--- DATI PRENOTAZIONE ---\n";
echo "ID: {$reservation->id}\n";
echo "Codice Voucher: {$reservation->coupon_code}\n";
echo "Cliente: {$reservation->nome_cliente} {$reservation->cognome_cliente}\n";
echo "Beneficiario: {$reservation->nome_beneficiario} {$reservation->cognome_beneficiario}\n";
echo "Data scadenza nel DB: {$reservation->data_scadenza}\n";

$location = $reservation->location;

$dataEmail = [
    'name' => $reservation->company_cliente ? $reservation->company_cliente : $reservation->nome_cliente,
    'surname' => $reservation->company_cliente ? '' : $reservation->cognome_cliente,
    'beneficiarioName' => $reservation->nome_beneficiario,
    'beneficiarioSurname' => $reservation->cognome_beneficiario,
    'activity_name' => $reservation->nome_activity,
    'details' => $reservation->details_activity,
    'note' => $reservation->note_activity,
    'description' => $reservation->description_activity,
    'prenotare' => $reservation->prenotare_activity,
    'coupon_code' => $reservation->coupon_code,
    'expiration_date' => $reservation->data_scadenza, // Usa la data dal DB
    'title' => "Il suo buono regalo per " . $location->name,
    'logo' => $location->logo,
    'telefono' => $location->telefono,
    'location_name' => $location->name,
    'ccn' => 'shop@antoninocannavacciuolo.it',
];

$dataEmail['fileName'] = 'Voucher ' . $dataEmail['name'] . ' ' . $dataEmail['surname'] . ' - ' . $dataEmail['coupon_code'] . '.pdf';

echo "\n--- GENERAZIONE PDF ---\n";
echo "Nome file: {$dataEmail['fileName']}\n";
echo "Data scadenza che verrà usata: {$dataEmail['expiration_date']}\n";

// Chiedi conferma
echo "\n⚠️  Vuoi rigenerare il PDF? (scrivi 'SI' per confermare): ";
$handle = fopen("php://stdin", "r");
$confirm = trim(fgets($handle));
fclose($handle);

if ($confirm !== 'SI') {
    echo "\n❌ Operazione annullata.\n";
    exit(0);
}

try {
    // Crea la directory se non esiste
    $vouchersDir = storage_path('app/vouchers');
    if (!file_exists($vouchersDir)) {
        mkdir($vouchersDir, 0755, true);
        echo "✅ Directory vouchers creata\n";
    }
    
    // Genera il PDF
    $pdfPath = $vouchersDir . '/' . $dataEmail['fileName'];
    PDF::loadView('emails.pdf', $dataEmail)
        ->setPaper('a4', 'portrait')
        ->save($pdfPath);
    
    echo "✅ PDF generato con successo!\n";
    echo "Path: {$pdfPath}\n";
    echo "Dimensione: " . number_format(filesize($pdfPath) / 1024, 2) . " KB\n";
    
    // Mostra la data formattata come apparirà nel PDF
    $formattedDate = \Carbon\Carbon::parse($dataEmail['expiration_date'])->format('d/m/Y');
    echo "\nData di scadenza nel PDF: {$formattedDate}\n";
    
} catch (\Exception $e) {
    echo "❌ Errore durante la generazione del PDF:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== RIGENERAZIONE COMPLETATA ===\n";
