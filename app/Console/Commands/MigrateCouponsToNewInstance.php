<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IpraticoAPIService;
use App\Models\Reservation;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;

class MigrateCouponsToNewInstance extends Command
{
    protected $signature = 'coupons:migrate {--all : Processa tutte le reservation, non solo la prima} {--dry-run : Simula senza scrivere}';
    protected $description = 'Migra coupon dalla vecchia istanza iPratico alla nuova: ricrea clienti e coupon';

    private string $oldKey = '19362:c2bdfd00-d2bf-45a5-ace9-e9c12afc10a5';
    private string $newKey = '20152:2b1ea297-ced9-4a7d-bfaa-bf5df151a1ed';

    public function handle()
    {
        $processAll = $this->option('all');
        $dryRun = $this->option('dry-run');

        $query = Reservation::whereNotNull('ipratico_client_id')
            ->where('ipratico_client_id', '!=', '')
            ->whereNotNull('coupon_code')
            ->where('coupon_code', '!=', '')
            ->orderBy('id');

        $reservations = $processAll ? $query->get() : $query->take(1)->get();

        $this->info("Reservation da processare: {$reservations->count()}");
        if ($dryRun) $this->warn("🔶 DRY RUN - nessuna scrittura");
        $this->newLine();

        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($reservations as $reservation) {
            $this->info("=== Reservation #{$reservation->id} - Coupon: {$reservation->coupon_code} ===");
            $this->line("  Cliente: {$reservation->nome_cliente} {$reservation->cognome_cliente}");
            $this->line("  Email: {$reservation->email_cliente}");
            $this->line("  Old iPratico client ID: {$reservation->ipratico_client_id}");

            // 1. Leggi dati cliente dalla VECCHIA istanza
            $this->line("  → Lettura cliente dalla vecchia istanza...");
            sleep(2); // Throttling per evitare blocchi iPratico
            $oldClient = IpraticoAPIService::api('GET', 'business-actors/' . $reservation->ipratico_client_id, [], $this->oldKey);

            if (isset($oldClient->error)) {
                $this->error("  ❌ Errore lettura vecchia istanza: " . json_encode($oldClient->error));
                $errors++;
                continue;
            }

            if (!$oldClient || !isset($oldClient->value)) {
                $this->error("  ❌ Cliente non trovato sulla vecchia istanza");
                $errors++;
                continue;
            }

            $this->line("  ✓ Cliente trovato: {$oldClient->value->surnameOrCompanyName}");

            // 2. Controlla se il cliente esiste già sulla NUOVA istanza (per fiscalCode)
            $fiscalCode = $oldClient->value->fiscalCode ?? null;
            $this->line("  → Controllo se esiste sulla nuova istanza (CF: {$fiscalCode})...");

            $existingClient = null;
            if ($fiscalCode) {
                sleep(2);
                $searchResult = IpraticoAPIService::api('GET', 'business-actors', ['fiscalCode' => $fiscalCode], $this->newKey);
                if (!isset($searchResult->error) && is_array($searchResult) && count($searchResult) > 0) {
                    $existingClient = $searchResult[0];
                    $this->warn("  ⚠️  Cliente già esiste sulla nuova istanza: {$existingClient->id}");
                }
            }

            // 3. Crea o usa il cliente sulla nuova istanza
            $newClientId = null;
            if ($existingClient) {
                $newClientId = $existingClient->id;
            } else {
                $this->line("  → Creazione cliente sulla nuova istanza...");

                // Prepara il body dal vecchio cliente
                $body = $this->buildClientBody($oldClient);

                if ($dryRun) {
                    $this->line("  🔶 DRY RUN: creerebbe cliente con body: " . json_encode($body, JSON_UNESCAPED_UNICODE));
                    $newClientId = 'DRY_RUN_CLIENT_ID';
                } else {
                    sleep(2);
                    $newClient = IpraticoAPIService::api('POST', 'business-actors', $body, $this->newKey);

                    if (isset($newClient->error)) {
                        $this->error("  ❌ Errore creazione cliente: " . json_encode($newClient->error));
                        $errors++;
                        continue;
                    }

                    $newClientId = $newClient->id;
                    $this->line("  ✓ Cliente creato: {$newClientId}");
                }
            }

            // 4. Aggiorna ipratico_client_id nella reservation
            $oldClientId = $reservation->ipratico_client_id;
            if (!$dryRun) {
                $reservation->ipratico_client_id = $newClientId;
                $reservation->save();
                $this->line("  ✓ ipratico_client_id aggiornato: {$oldClientId} → {$newClientId}");
            } else {
                $this->line("  🔶 DRY RUN: aggiornerebbe ipratico_client_id da {$oldClientId} a {$newClientId}");
            }

            // 5. Crea il coupon sulla nuova istanza
            $this->line("  → Creazione coupon {$reservation->coupon_code} sulla nuova istanza...");

            // Trova l'activity per il valore e l'ipratico_id
            $activity = Activity::where('name', $reservation->nome_activity)->first();
            $activityIpraticoId = $activity?->ipratico_id;

            // Calcola data scadenza
            $endDate = $reservation->data_scadenza
                ? \Carbon\Carbon::parse($reservation->data_scadenza)->format('Y-m-d\TH:i:sP')
                : \Carbon\Carbon::now()->addMonths(6)->format('Y-m-d\TH:i:sP');

            $promoBody = [
                'shared' => false,
                'isReusable' => 1,
                'isActive' => true,
                'isCumulable' => true,
                'constraints' => [
                    'applyOnFinalPrice' => true,
                    'allowedBusinessActorIds' => [$newClientId],
                ],
                'finalUnitaryPriceVariation' => [
                    'isPercentage' => 'false',
                    'variation' => (float) $reservation->amount,
                ],
                'validity' => [
                    'endDate' => $endDate,
                ],
                'code' => $reservation->coupon_code,
                'name' => $reservation->coupon_code,
                'note' => 'migrato da vecchia istanza',
                'preselectedBusinessActorId' => $newClientId,
            ];

            // Aggiungi il prodotto iPratico se disponibile
            if ($activityIpraticoId) {
                $promoBody['constraints']['productsAppliedDiscount'] = [$activityIpraticoId];
            }

            // Aggiungi invoice details se disponibili
            if ($reservation->n_fattura) {
                $promoBody['invoiceDetails'] = [
                    'number' => $reservation->n_fattura,
                    'date' => $reservation->data_fattura ? \Carbon\Carbon::parse($reservation->data_fattura)->format('Y-m-d') : now()->format('Y-m-d'),
                    'line' => 1,
                ];
            }

            if ($dryRun) {
                $this->line("  🔶 DRY RUN: creerebbe coupon con body: " . json_encode($promoBody, JSON_UNESCAPED_UNICODE));
                $success++;
            } else {
                sleep(2);
                $couponResult = IpraticoAPIService::api('POST', 'promo-codes', $promoBody, $this->newKey);

                if (isset($couponResult->error)) {
                    $code = $couponResult->error->code ?? ($couponResult->error ?? 'unknown');
                    if ($code == 412 || str_contains(json_encode($couponResult), '412')) {
                        $this->warn("  ⚠️  Coupon già esiste su iPratico (412)");
                        $skipped++;
                    } else {
                        $this->error("  ❌ Errore creazione coupon: " . json_encode($couponResult));
                        $errors++;
                        continue;
                    }
                } else {
                    $newCouponId = $couponResult->id ?? 'N/A';
                    // Aggiorna ipratico_id nella reservation
                    $reservation->ipratico_id = $newCouponId;
                    $reservation->save();
                    $this->line("  ✓ Coupon creato: {$newCouponId}");
                    $success++;
                }
            }

            $this->newLine();

            // Se non --all, fermati dopo il primo
            if (!$processAll) {
                $this->info("🛑 Test mode: processata solo la prima reservation. Usa --all per processarle tutte.");
                break;
            }
        }

        $this->newLine();
        $this->info("=== RIEPILOGO ===");
        $this->info("Successo: {$success}");
        $this->info("Skippati (già esistenti): {$skipped}");
        $this->info("Errori: {$errors}");
    }

    private function buildClientBody($oldClient): array
    {
        $value = $oldClient->value;
        $personality = $value->personality ?? '00';
        $isCompany = ($personality === '01');

        $email = $value->emails[0] ?? '';
        $place = $value->places[0] ?? null;
        $phone = $value->phones[0] ?? '';

        $body = [
            'policies' => ['marketing' => true, 'privacy' => true],
            'surnameOrCompanyName' => $value->surnameOrCompanyName ?? '',
            'personality' => $personality,
            'fiscalCode' => $value->fiscalCode ?? '',
            'emails' => [$email],
            'phones' => [$phone],
            'places' => [[
                'isFiscalAddress' => true,
                'address' => $place->address ?? '',
                'city' => $place->city ?? '',
                'province' => $place->province ?? '',
                'nation' => $place->nation ?? 'IT',
                'zipCode' => $place->zipCode ?? '',
            ]],
        ];

        if (!$isCompany && isset($value->personal)) {
            $body['personal'] = ['foreName' => $value->personal->foreName ?? ''];
        }

        if (isset($value->invoiceData->invoicingEndpoint)) {
            $body['invoiceData'] = ['invoicingEndpoint' => $value->invoiceData->invoicingEndpoint];
        }

        return $body;
    }
}
