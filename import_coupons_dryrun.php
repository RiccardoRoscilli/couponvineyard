<?php
/**
 * Script dry-run per importare coupon da file Excel ESTRAZIONE (1).xlsx
 * Simula le chiamate API apiStore senza creare realmente i coupon su iPratico.
 *
 * Uso: php import_coupons_dryrun.php [--live]
 *   Senza --live: solo dry-run (stampa i payload senza chiamare l'API)
 *   Con --live: esegue realmente le chiamate API
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;

$dryRun = !in_array('--live', $argv);
$apiUrl = env('APP_URL', 'http://couponvineyard.test') . '/api/store';
$username = 'daniele.larosa@uniwix.com';
$password = 'XRG*=u)t+v9;=tK';

echo "=== IMPORT COUPON DA EXCEL ===" . PHP_EOL;
echo "Modalità: " . ($dryRun ? "🔶 DRY RUN (nessuna chiamata API)" : "🔴 LIVE") . PHP_EOL;
echo PHP_EOL;

// Leggi il file Excel
$spreadsheet = IOFactory::load(__DIR__ . '/ESTRAZIONE (1).xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();
$header = array_shift($rows); // rimuovi header

echo "Righe trovate: " . count($rows) . PHP_EOL . PHP_EOL;

// Mappa colonne (filtra null)
$colMap = [];
foreach ($header as $idx => $col) {
    if ($col !== null) {
        $colMap[$col] = $idx;
    }
}

foreach ($rows as $i => $row) {
    $couponCode = trim($row[$colMap['Codice promo']] ?? '');
    $fiscalCode = trim($row[$colMap['Codice Fiscale'] ?? $colMap['Fiscal_Code']] ?? '');
    $piva = trim($row[$colMap['PIVA'] ?? ''] ?? '');
    $surname = trim($row[$colMap['Denominazione/Cognome'] ?? $colMap['Surname']] ?? '');
    $name = trim($row[$colMap['Nome'] ?? $colMap['Name']] ?? '');
    $email = trim($row[$colMap['Email'] ?? ''] ?? '');
    $phone = trim($row[$colMap['Phone'] ?? ''] ?? '');
    $address = trim($row[$colMap['Address'] ?? ''] ?? '');
    $city = trim($row[$colMap['City'] ?? ''] ?? '');
    $zip = trim($row[$colMap['Zip_Code'] ?? ''] ?? '');
    $province = trim($row[$colMap['Province'] ?? ''] ?? '');
    $nation = trim($row[$colMap['Nation'] ?? ''] ?? 'IT');
    $ipraticoId = trim($row[$colMap['Id'] ?? ''] ?? '');

    if (empty($couponCode)) {
        echo "⚠️  Riga " . ($i + 2) . ": codice promo vuoto, skip" . PHP_EOL;
        continue;
    }

    // Determina tipo cliente
    $clientType = 'Persona Fisica';
    $companyName = '';
    if (!empty($piva) && preg_match('/^\d{11}$/', $piva)) {
        $clientType = 'Azienda';
        $companyName = $surname;
    }

    // TODO: Devi specificare quale activity SKU e fattura associare a ogni riga
    // Per ora uso valori placeholder
    $activitySku = '00071_-1'; // Prologo per una persona - CAMBIA SE NECESSARIO
    $invoiceNumber = $couponCode;
    $invoiceDate = date('Y-m-d');

    $payload = [
        'request' => [
            'client' => [
                'name' => $name,
                'surnameOrCompanyName' => $surname,
                'fiscalCode' => $fiscalCode ?: ($piva ?: '99999999999'),
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'zipCode' => $zip,
                'province' => $province,
                'nation' => $nation,
            ],
            'beneficiary' => [
                'name' => $name,
                'surname' => $surname,
                'note' => '',
            ],
            'invoice' => [
                'invoice_increment' => $invoiceNumber,
                'invoice_number' => $couponCode,
                'invoice_date' => $invoiceDate,
                'invoice_line' => 1,
            ],
            'activity' => [
                'activity_sku' => $activitySku,
                'activity_language' => 'IT',
            ],
        ],
    ];

    echo "--- Riga " . ($i + 2) . " ---" . PHP_EOL;
    echo "  Codice promo: {$couponCode}" . PHP_EOL;
    echo "  Cliente: {$name} {$surname} ({$clientType})" . PHP_EOL;
    echo "  CF/PIVA: " . ($fiscalCode ?: $piva ?: 'N/A') . PHP_EOL;
    echo "  Email: {$email}" . PHP_EOL;
    echo "  iPratico ID: {$ipraticoId}" . PHP_EOL;
    echo "  Activity SKU: {$activitySku}" . PHP_EOL;

    if ($dryRun) {
        echo "  🔶 DRY RUN - Payload:" . PHP_EOL;
        echo "  " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    } else {
        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();
                echo "  ✅ Successo: " . json_encode($body) . PHP_EOL;
            } else {
                echo "  ❌ Errore HTTP {$response->status()}: " . $response->body() . PHP_EOL;
            }
        } catch (\Exception $e) {
            echo "  ❌ Eccezione: " . $e->getMessage() . PHP_EOL;
        }
    }
    echo PHP_EOL;
}

echo "=== FINE ===" . PHP_EOL;
