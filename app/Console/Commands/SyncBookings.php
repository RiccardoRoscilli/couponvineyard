<?php

// app/Console/Commands/SyncBookings.php
namespace App\Console\Commands;

use App\Models\PrenotaLocation;
use App\Models\Location;
use App\Models\{Booking, BookingWebhookLog};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncBookings extends Command
{
    protected $signature = 'bookings:sync
    {--from=}
    {--to=}
    {--prenota= : Filtra per restaurant_id di Prenota-Web}
    {--location= : Filtra per location_id interno}
    {--dry-run : Non inviare, solo log}
    {--show-payload : In dry-run stampa i payload inviabili}';

    protected $description = 'Sincronizza prenotazioni (multi-location), storicizza e invia al webhook.';

    public function handle(): int
    {
        $base = rtrim((string) config('services.prenota.base'), '/');
        $version = (string) config('services.prenota.version', '2');
        $auth = (string) config('services.prenota.auth'); // es. "Basic xxx"
        $webhook = (string) config('services.leadconnector.webhook');
        $slopeWebhook = (string) config('services.leadconnector.slope_webhook');

        $daysAhead = (int) env('PRENOTA_API_DAYS_AHEAD', 3);
        $from = $this->option('from') ?: Carbon::today()->toDateString();
        $to = $this->option('to') ?: Carbon::today()->addDays($daysAhead)->toDateString();
        $fPrenota = $this->option('prenota');   // es. "1"
        $fLocation = $this->option('location');  // id interno

        $overallOk = true;
        
        // Chiama il webhook Slope all'inizio della sincronizzazione
        $this->callSlopeWebhook($slopeWebhook);

        /**
         * 1) Costruisci il target su PrenotaLocation (se tabella presente e popolata)
         */
        $targets = collect();
        try {
            // Se la tabella esiste e contiene dati, usiamola come fonte principale
            $q = PrenotaLocation::query()->where('is_enabled', true);
            if ($fPrenota !== null && $fPrenota !== '') {
                $q->where('restaurant_id', (string) $fPrenota);
            }
            if ($fLocation !== null && $fLocation !== '') {
                $q->where('location_id', (int) $fLocation);
            }
            $targets = $q->get();
        } catch (\Throwable $e) {
            // Se il Model/Tabella non esiste, passeremo al fallback su locations
        }

        /**
         * 2) Fallback: se non troviamo nulla in PrenotaLocation,
         *    usiamo la tua tabella locations con campo prenota_web_restaurant_id
         */
        $useFallbackLocations = $targets->isEmpty();
        if ($useFallbackLocations) {
            $lq = Location::query()->whereNotNull('prenota_web_restaurant_id');
            if ($fPrenota !== null && $fPrenota !== '') {
                $lq->where('prenota_web_restaurant_id', (string) $fPrenota);
            }
            if ($fLocation !== null && $fLocation !== '') {
                $lq->where('id', (int) $fLocation);
            }
            $fallbackLocations = $lq->get();

            if ($fallbackLocations->isEmpty()) {
                $this->warn('Nessuna location trovata: né PrenotaLocation attive, né locations con prenota_web_restaurant_id.');
                return self::SUCCESS;
            }

            foreach ($fallbackLocations as $loc) {
                $this->processSingleTargetViaLocation(
                    $loc,
                    $base,
                    $version,
                    $auth,
                    $webhook,
                    $from,
                    $to,
                    $overallOk
                );
            }

            return $overallOk ? self::SUCCESS : self::FAILURE;
        }

        /**
         * 3) Percorso principale: PrenotaLocation
         */
        foreach ($targets as $pl) {
            $restaurantId = (string) $pl->restaurant_id;
            $locId = $pl->location_id; // può essere null se non mappato
            $locName = $pl->name ?: ('Ristorante #' . $pl->restaurant_id);

            $url = sprintf(
                '%s/api/prenotazioni?v=%s&idRistorante=%s&data=%s&dataFine=%s',
                $base,
                $version,
                $restaurantId,
                $from,
                $to
            );

            $this->info("PrenotaLocation #{$pl->id} ({$locName}) → GET $url");

            $booking = new Booking([
                'prenota_location_id' => $pl->id,          // se la colonna non esiste verrà ignorata
                'location_id' => $locId,           // può essere null
                'restaurant_id' => (string) $restaurantId,
                'date_from' => $from,
                'date_to' => $to,
                'api_version' => $version,
                'request_url' => $url,
                'executed_at' => Carbon::now(),
            ]);

            try {
                $resp = Http::withHeaders([
                    'Authorization' => $auth,
                    'Accept' => 'application/json',
                ])->timeout(60)->get($url);

                $booking->http_status = $resp->status();

                if (!$resp->successful()) {
                    $booking->success = false;
                    $booking->error_message = 'HTTP ' . $resp->status() . ' - ' . $resp->body();
                    $booking->save();
                    $this->error("{$locName}: " . $booking->error_message);
                    $overallOk = false;
                    continue;
                }

                $json = $resp->json();
                $booking->response_json = $json;
                $booking->num_reservations = (int) ($json['numero'] ?? 0);
                $booking->success = true;
                $booking->save();

                $sent = 0;

                foreach ((array) ($json['prenotazioni'] ?? []) as $p) {
                    $idPren = (int) ($p['idPrenotazione'] ?? 0);
                    if (!$idPren)
                        continue;

                    $lastMod = isset($p['dataOraUltimaModifica']) ? (string) $p['dataOraUltimaModifica'] : null;

                    // Idempotenza: prima per prenota_location_id (se disponibile), altrimenti per location_id
                    $idem = BookingWebhookLog::query()
                        ->when(true, function ($q) use ($pl, $locId) {
                            if ($pl && $pl->id) {
                                $q->where('prenota_location_id', $pl->id);
                            } else {
                                $q->where('location_id', $locId);
                            }
                        })
                        ->where('id_prenotazione', $idPren)
                        ->where('last_modified', $lastMod)
                        ->exists();

                    if ($idem)
                        continue;

                    // Payload ridotto ai soli campi richiesti
                    $prefisso = $p['prefisso'] ?? '';
                    $telefono = $p['telefono'] ?? '';
                    
                    // Se prefisso vuoto e numero inizia con 3 (cellulare IT), aggiungi +39
                    if (empty($prefisso) && !empty($telefono) && str_starts_with($telefono, '3')) {
                        $prefisso = '+39';
                    }
                    
                    // Concatena e pulisci: rimuovi spazi, punti, trattini
                    $telefonoCompleto = $prefisso . $telefono;
                    $telefonoCompleto = preg_replace('/[\s.\-]+/', '', $telefonoCompleto);
                    $telefonoCompleto = trim($telefonoCompleto);

                    $payload = [
                        'idPrenotazione' => (int) ($p['idPrenotazione'] ?? 0),
                        'data' => $p['data'] ?? null,
                        'ora' => $p['ora'] ?? null,
                        'cliente' => $p['cliente'] ?? null,
                        'telefono' => $telefonoCompleto ?: null,
                        'email' => $p['email'] ?? null,
                        'lingua' => $p['lingua'] ?? null,
                        'newsletter' => $p['newsletter'] ?? null,   // "S" / "N"
                        'noteInt' => $p['noteInt'] ?? null,
                        'stato' => isset($p['stato']) ? (int) $p['stato'] : null,
                        'situazione' => isset($p['situazione']) ? (int) $p['situazione'] : null,
                        'location' => $locName,
                    ];

                    if ($this->option('dry-run')) {
                        // Log completo su file
                        \Log::info('DRY-RUN webhook payload', [
                            'prenota_location_id' => $pl->id ?? null,
                            'restaurant_id' => $restaurantId,
                            'location' => $locName,
                            'payload' => $payload,
                        ]);

                        // Stampa a video opzionale (riassunto) se --show-payload
                        if ($this->option('show-payload')) {
                            // Sintesi “umana” su una riga
                            $summary = sprintf(
                                'DRY-RUN → %s | #%d %s %s %s (%s) [%s/%s]',
                                $locName,
                                (int) ($payload['idPrenotazione'] ?? 0),
                                $payload['data'] ?? '—',
                                $payload['ora'] ?? '—',
                                $payload['cliente'] ?? '—',
                                $payload['email'] ?? '—',
                                (string) ($payload['stato'] ?? '—'),
                                (string) ($payload['situazione'] ?? '—')
                            );
                            $this->line($summary);

                            // Se vuoi anche il JSON “bello”:
                            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                        }

                        continue; // non inviare in dry-run
                    }

                    $wh = Http::asJson()->timeout(30)->post($webhook, $payload);

                    BookingWebhookLog::create([
                        'booking_id' => $booking->id,
                        'prenota_location_id' => $pl->id,
                        'location_id' => $locId,
                        'id_prenotazione' => $idPren,
                        'last_modified' => $lastMod,
                        'http_status' => $wh->status(),
                        'success' => $wh->successful(),
                        'error_message' => $wh->successful() ? null : ('HTTP ' . $wh->status() . ' - ' . $wh->body()),
                        // 'payload_sent' => $payload, 'response_body' => $wh->body(), // se hai aggiunto queste colonne
                    ]);

                    if ($wh->successful()) {
                        $sent++;
                    } else {
                        Log::warning('Webhook LeadConnector failed', [
                            'prenota_location_id' => $pl->id,
                            'location_id' => $locId,
                            'idPrenotazione' => $idPren,
                            'status' => $wh->status(),
                            'body' => $wh->body(),
                        ]);
                    }
                }

                $this->info("{$locName}: inviate al webhook {$sent} prenotazioni.");
            } catch (\Throwable $e) {
                $booking->success = false;
                $booking->error_message = $e->getMessage();
                $booking->save();

                $this->error("{$locName}: " . $e->getMessage());
                $overallOk = false;
            }
        }

        return $overallOk ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Fallback path: usa direttamente una Location interna che ha prenota_web_restaurant_id
     */
    private function processSingleTargetViaLocation(
        Location $loc,
        string $base,
        string $version,
        string $auth,
        string $webhook,
        string $from,
        string $to,
        bool &$overallOk
    ): void {
        $restaurantId = (string) $loc->prenota_web_restaurant_id;

        $url = sprintf(
            '%s/api/prenotazioni?v=%s&idRistorante=%s&data=%s&dataFine=%s',
            $base,
            $version,
            $restaurantId,
            $from,
            $to
        );

        $this->info("Location #{$loc->id} {$loc->name} → GET $url");

        $booking = new Booking([
            'location_id' => $loc->id,
            'restaurant_id' => (string) $restaurantId,
            'date_from' => $from,
            'date_to' => $to,
            'api_version' => $version,
            'request_url' => $url,
            'executed_at' => Carbon::now(),
        ]);

        try {
            $resp = Http::withHeaders([
                'Authorization' => $auth,
                'Accept' => 'application/json',
            ])->timeout(60)->get($url);

            $booking->http_status = $resp->status();

            if (!$resp->successful()) {
                $booking->success = false;
                $booking->error_message = 'HTTP ' . $resp->status() . ' - ' . $resp->body();
                $booking->save();
                $this->error("{$loc->name}: " . $booking->error_message);
                $overallOk = false;
                return;
            }

            $json = $resp->json();
            $booking->response_json = $json;
            $booking->num_reservations = (int) ($json['numero'] ?? 0);
            $booking->success = true;
            $booking->save();

            $sent = 0;

            foreach ((array) ($json['prenotazioni'] ?? []) as $p) {
                $idPren = (int) ($p['idPrenotazione'] ?? 0);
                if (!$idPren)
                    continue;

                $lastMod = isset($p['dataOraUltimaModifica']) ? (string) $p['dataOraUltimaModifica'] : null;

                $idem = BookingWebhookLog::query()
                    ->where('location_id', $loc->id)
                    ->where('id_prenotazione', $idPren)
                    ->where('last_modified', $lastMod)
                    ->exists();
                if ($idem)
                    continue;

                $prefisso = $p['prefisso'] ?? '';
                $telefono = $p['telefono'] ?? '';
                
                // Se prefisso vuoto e numero inizia con 3 (cellulare IT), aggiungi +39
                if (empty($prefisso) && !empty($telefono) && str_starts_with($telefono, '3')) {
                    $prefisso = '+39';
                }
                
                // Concatena e pulisci: rimuovi spazi, punti, trattini
                $telefonoCompleto = $prefisso . $telefono;
                $telefonoCompleto = preg_replace('/[\s.\-]+/', '', $telefonoCompleto);
                $telefonoCompleto = trim($telefonoCompleto);

                $payload = [
                    'idPrenotazione' => (int) ($p['idPrenotazione'] ?? 0),
                    'data' => $p['data'] ?? null,
                    'ora' => $p['ora'] ?? null,
                    'cliente' => $p['cliente'] ?? null,
                    'telefono' => $telefonoCompleto ?: null,
                    'email' => $p['email'] ?? null,
                    'lingua' => $p['lingua'] ?? null,
                    'newsletter' => $p['newsletter'] ?? null,
                    'noteInt' => $p['noteInt'] ?? null,
                    'stato' => isset($p['stato']) ? (int) $p['stato'] : null,
                    'situazione' => isset($p['situazione']) ? (int) $p['situazione'] : null,
                ];

                if ($this->option('dry-run')) {
                    Log::info('DRY-RUN webhook payload', [
                        'location_id' => $loc->id,
                        'restaurant_id' => $restaurantId,
                        'payload' => $payload,
                    ]);
                    continue;
                }

                $wh = Http::asJson()->timeout(30)->post($webhook, $payload);

                BookingWebhookLog::create([
                    'booking_id' => $booking->id,
                    'location_id' => $loc->id,
                    'id_prenotazione' => $idPren,
                    'last_modified' => $lastMod,
                    'http_status' => $wh->status(),
                    'success' => $wh->successful(),
                    'error_message' => $wh->successful() ? null : ('HTTP ' . $wh->status() . ' - ' . $wh->body()),
                    // 'payload_sent' => $payload, 'response_body' => $wh->body(),
                ]);

                if ($wh->successful()) {
                    $sent++;
                } else {
                    Log::warning('Webhook LeadConnector failed', [
                        'location_id' => $loc->id,
                        'idPrenotazione' => $idPren,
                        'status' => $wh->status(),
                        'body' => $wh->body(),
                    ]);
                }
            }

            $this->info("{$loc->name}: inviate al webhook {$sent} prenotazioni.");
        } catch (\Throwable $e) {
            $booking->success = false;
            $booking->error_message = $e->getMessage();
            $booking->save();

            $this->error("{$loc->name}: " . $e->getMessage());
            $overallOk = false;
        }
    }

    /**
     * Chiama il webhook Slope per notificare l'inizio della sincronizzazione
     */
    private function callSlopeWebhook(string $webhookUrl): void
    {
        if (empty($webhookUrl)) {
            $this->warn('Slope webhook URL non configurato, skip.');
            return;
        }

        try {
            $this->info("Chiamata webhook Slope: {$webhookUrl}");
            
            $response = Http::timeout(30)->post($webhookUrl, [
                'event' => 'sync_bookings_started',
                'timestamp' => Carbon::now()->toIso8601String(),
                'source' => 'erpcoupon_sync_bookings',
            ]);

            if ($response->successful()) {
                $this->info('✓ Webhook Slope chiamato con successo');
            } else {
                $this->warn("Webhook Slope fallito: HTTP {$response->status()}");
                Log::warning('Slope webhook failed', [
                    'url' => $webhookUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->warn("Errore chiamata webhook Slope: {$e->getMessage()}");
            Log::error('Slope webhook error', [
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
