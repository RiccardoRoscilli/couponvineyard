<?php

namespace App\Services;

use App\Models\Location;
use App\Models\SlopeBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Slope Service
 * 
 * Business logic layer for booking synchronization with Slope API.
 * Handles data processing, idempotency checks, and database operations.
 */
class SlopeService
{
    private SlopeApiClient $apiClient;

    public function __construct(SlopeApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync bookings for a specific location
     * 
     * @param Location $location
     * @param string $from Date in Y-m-d format
     * @param string $to Date in Y-m-d format
     * @return array{created: int, updated: int, errors: array}
     */
    public function syncBookingsForLocation(Location $location, string $from, string $to): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'canceled' => 0,
            'errors' => [],
        ];

        // Fetch bookings from API
        $result = $this->apiClient->getBookings($from, $to);

        if (!$result['success']) {
            $stats['errors'][] = $result['error'];
            Log::error('Failed to fetch bookings from Slope API', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'error' => $result['error'],
                'status' => $result['status'],
            ]);
            return $stats;
        }

        $bookingsData = $result['data']['data'] ?? [];
        $hasNextPage = $result['data']['pagination']['hasNextPage'] ?? false;

        Log::info('Fetched bookings from Slope API', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'count' => count($bookingsData),
            'has_next_page' => $hasNextPage,
        ]);

        // Process each booking
        foreach ($bookingsData as $bookingData) {
            try {
                $slopeBookingId = $bookingData['id'] ?? null;
                
                if (!$slopeBookingId) {
                    $stats['errors'][] = 'Booking missing ID';
                    continue;
                }

                $lastUpdateDate = $bookingData['lastUpdateDate'] ?? null;

                // Check if booking is already up-to-date
                if ($this->isBookingUpToDate($location->id, $slopeBookingId, $lastUpdateDate)) {
                    continue;
                }

                // Process the booking
                $booking = $this->processBooking($bookingData, $location);

                if ($booking) {
                    if ($booking->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                    
                    // Conta le cancellate
                    if ($booking->stato === 'Cancellata') {
                        $stats['canceled']++;
                    }
                    
                    // Chiama il webhook per TUTTE le prenotazioni (anche cancellate)
                    $this->sendBookingToWebhook($booking, $location);
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "Error processing booking {$slopeBookingId}: {$e->getMessage()}";
                Log::error('Error processing Slope booking', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $slopeBookingId ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Process a single booking from API response
     * 
     * @param array $bookingData Raw booking data from API
     * @param Location $location The location this booking belongs to
     * @return SlopeBooking|null
     */
    public function processBooking(array $bookingData, Location $location): ?SlopeBooking
    {
        // Extract required fields
        $slopeBookingId = $bookingData['id'] ?? null;
        $arrivalDate = $bookingData['stayPeriod']['arrival'] ?? null;
        $departureDate = $bookingData['stayPeriod']['departure'] ?? null;

        // Validate required fields
        if (!$slopeBookingId || !$arrivalDate) {
            Log::warning('Slope booking missing required fields', [
                'booking_data' => $bookingData,
                'location_id' => $location->id,
            ]);
            return null;
        }

        // Extract customer info (try primaryGuest first, then order.customer)
        $primaryGuest = $bookingData['primaryGuest'] ?? null;
        $orderCustomer = $bookingData['order']['customer'] ?? null;
        $customer = $primaryGuest ?? $orderCustomer;

        // Build cliente (nome completo)
        $firstName = $customer['firstName'] ?? '';
        $lastName = $customer['lastName'] ?? '';
        $cliente = trim("{$firstName} {$lastName}");
        
        if (empty($cliente)) {
            Log::warning('Slope booking missing customer name', [
                'slope_booking_id' => $slopeBookingId,
                'location_id' => $location->id,
            ]);
            return null;
        }

        // Extract contact info
        $email = $customer['primaryEmail']['address'] ?? null;
        $telefono = $this->normalizePhoneNumber($customer['primaryPhoneNumber']['number'] ?? null);
        $lingua = $customer['language'] ?? null;

        // Newsletter: check if marketing authorization is given
        $newsletter = null;
        if (isset($customer['primaryEmail']['isMarketingAuthorizationGiven'])) {
            $newsletter = $customer['primaryEmail']['isMarketingAuthorizationGiven'] ? 'S' : 'N';
        }

        // Extract guest counts
        $adults = $bookingData['guestCounts']['adults'] ?? 0;
        $children = $bookingData['guestCounts']['children'] ?? 0;

        // Extract status flags
        $isCanceled = $bookingData['isCanceled'] ?? false;
        $lastUpdateDate = $this->parseDateTime($bookingData['lastUpdateDate'] ?? null);

        // Check-in time (se disponibile, altrimenti default 00:00)
        $checkInDate = $this->parseDateTime($bookingData['checkInDate'] ?? null);
        $ora = $checkInDate ? $checkInDate->format('H:i:s') : '00:00:00';

        // Map situazione
        $situazione = $this->mapSituazione($bookingData);

        // Note interne (possiamo usare lodging name o altri dati)
        $noteInt = $this->buildNoteInt($bookingData);

        // Determina lo stato in base alle modifiche
        $existingBooking = SlopeBooking::where('location_id', $location->id)
            ->where('slope_booking_id', $slopeBookingId)
            ->first();

        $stato = $this->determineBookingStatus($existingBooking, $arrivalDate, $departureDate, $isCanceled);

        // Create or update booking
        $booking = SlopeBooking::updateOrCreate(
            [
                'location_id' => $location->id,
                'slope_booking_id' => $slopeBookingId,
            ],
            [
                'data' => $arrivalDate,
                'ora' => $ora,
                'cliente' => $cliente,
                'telefono' => $telefono,
                'email' => $email,
                'lingua' => $lingua,
                'newsletter' => $newsletter,
                'note_int' => $noteInt,
                'stato' => $stato,
                'situazione' => $situazione,
                'departure_date' => $departureDate,
                'adults' => $adults,
                'children' => $children,
                'is_canceled' => $isCanceled,
                'last_update_date' => $lastUpdateDate,
                'synced_at' => Carbon::now(),
            ]
        );

        return $booking;
    }

    /**
     * Determine booking status based on changes
     * 
     * @param SlopeBooking|null $existingBooking
     * @param string $newArrivalDate
     * @param string|null $newDepartureDate
     * @param bool $isCanceled
     * @return string
     */
    private function determineBookingStatus(?SlopeBooking $existingBooking, string $newArrivalDate, ?string $newDepartureDate, bool $isCanceled): string
    {
        // Se è cancellata dall'API, stato = Cancellata
        if ($isCanceled) {
            return 'Cancellata';
        }

        // Se era cancellata ma ora non lo è più, stato = Attiva (riattivata)
        if ($existingBooking && $existingBooking->stato === 'Cancellata' && !$isCanceled) {
            return 'Attiva';
        }

        // Se è nuova, stato = Attiva
        if (!$existingBooking) {
            return 'Attiva';
        }

        // Controlla se le date sono cambiate
        $arrivalChanged = $existingBooking->data->format('Y-m-d') !== $newArrivalDate;
        $departureChanged = ($existingBooking->departure_date ? $existingBooking->departure_date->format('Y-m-d') : null) !== $newDepartureDate;

        // Se le date sono cambiate, stato = Modificata
        if ($arrivalChanged || $departureChanged) {
            return 'Modificata';
        }

        // Altrimenti stato = Attiva (nessuna modifica)
        return 'Attiva';
    }

    /**
     * Sync deleted bookings for a specific location
     * 
     * @param Location $location
     * @param string $from Date in Y-m-d format
     * @param string $to Date in Y-m-d format
     * @return array{canceled: int, errors: array}
     */
    public function syncCanceledBookingsForLocation(Location $location, string $from, string $to): array
    {
        $stats = [
            'canceled' => 0,
            'errors' => [],
        ];

        // Fetch deleted resources from API
        $result = $this->apiClient->getDeletedBookings($from, $to);

        if (!$result['success']) {
            $stats['errors'][] = $result['error'];
            Log::error('Failed to fetch deleted bookings from Slope API', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'error' => $result['error'],
                'status' => $result['status'],
            ]);
            return $stats;
        }

        $deletedResources = $result['data']['data'] ?? [];

        Log::info('Fetched deleted bookings from Slope API', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'count' => count($deletedResources),
        ]);

        // Mark each deleted booking as canceled
        foreach ($deletedResources as $resource) {
            try {
                // L'endpoint deleted-resources restituisce oggetti con resourceId
                $slopeBookingId = $resource['resourceId'] ?? null;
                
                if (!$slopeBookingId) {
                    $stats['errors'][] = 'Deleted resource missing resourceId';
                    continue;
                }

                // Get the booking before updating it
                $booking = SlopeBooking::where('location_id', $location->id)
                    ->where('slope_booking_id', $slopeBookingId)
                    ->first();

                if (!$booking) {
                    continue;
                }

                // Update to Cancellata status
                $booking->update([
                    'stato' => 'Cancellata',
                    'is_canceled' => true,
                    'synced_at' => Carbon::now(),
                ]);

                $stats['canceled']++;
                
                Log::info('Marked booking as canceled', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $slopeBookingId,
                ]);

                // Send webhook notification for cancellation
                $this->sendBookingToWebhook($booking->fresh(), $location);

            } catch (\Exception $e) {
                $stats['errors'][] = "Error marking booking as canceled {$slopeBookingId}: {$e->getMessage()}";
                Log::error('Error marking Slope booking as canceled', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $slopeBookingId ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Check and sync cancellations for recent bookings
     * Fetches non-canceled bookings from last 15 days and checks if they were deleted in Slope
     * 
     * @param Location $location
     * @param int $daysBack Number of days to look back (default 15)
     * @return array{canceled: int, checked: int, errors: array}
     */
    public function checkRecentCancellations(Location $location, int $daysBack = 15): array
    {
        $stats = [
            'checked' => 0,
            'canceled' => 0,
            'errors' => [],
        ];

        // Get bookings from last N days that are NOT in Cancellata status
        $fromDate = Carbon::now()->subDays($daysBack);
        
        $recentBookings = SlopeBooking::where('location_id', $location->id)
            ->where('data', '>=', $fromDate)
            ->where('stato', '!=', 'Cancellata')
            ->get();

        if ($recentBookings->isEmpty()) {
            Log::info('No recent non-canceled bookings to check', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'days_back' => $daysBack,
            ]);
            return $stats;
        }

        $stats['checked'] = $recentBookings->count();

        // Extract booking IDs
        $bookingIds = $recentBookings->pluck('slope_booking_id')->toArray();

        Log::info('Checking recent bookings for cancellations', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'booking_count' => count($bookingIds),
            'days_back' => $daysBack,
        ]);

        // Check with Slope API which ones are deleted
        $result = $this->apiClient->checkDeletedBookings($bookingIds);

        if (!$result['success']) {
            $stats['errors'][] = $result['error'];
            Log::error('Failed to check deleted bookings from Slope API', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'error' => $result['error'],
                'status' => $result['status'],
            ]);
            return $stats;
        }

        $deletedResources = $result['data']['data'] ?? [];

        Log::info('Received deleted bookings response', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'deleted_count' => count($deletedResources),
        ]);

        // Mark each deleted booking as canceled
        foreach ($deletedResources as $resource) {
            try {
                $slopeBookingId = $resource['resourceId'] ?? null;
                
                if (!$slopeBookingId) {
                    $stats['errors'][] = 'Deleted resource missing resourceId';
                    continue;
                }

                // Get the booking before updating it
                $booking = SlopeBooking::where('location_id', $location->id)
                    ->where('slope_booking_id', $slopeBookingId)
                    ->first();

                if (!$booking) {
                    continue;
                }

                // Update to Cancellata status
                $booking->update([
                    'stato' => 'Cancellata',
                    'is_canceled' => true,
                    'synced_at' => Carbon::now(),
                ]);

                $stats['canceled']++;
                
                Log::info('Marked booking as canceled', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $slopeBookingId,
                ]);

                // Send webhook notification for cancellation
                $this->sendBookingToWebhook($booking->fresh(), $location);

            } catch (\Exception $e) {
                $stats['errors'][] = "Error marking booking as canceled {$slopeBookingId}: {$e->getMessage()}";
                Log::error('Error marking Slope booking as canceled', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $slopeBookingId ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Map Slope booking to situazione field
     * 
     * @param array $bookingData
     * @return int|null
     */
    private function mapSituazione(array $bookingData): ?int
    {
        // Mapping da definire in base alle vostre esigenze
        // Esempio:
        // 1 = Normale
        // 2 = Overbooking
        // 3 = VIP
        
        if ($bookingData['isOverbooking'] ?? false) {
            return 2; // Overbooking
        }
        
        return 1; // Normale
    }

    /**
     * Build internal notes from booking data
     * 
     * @param array $bookingData
     * @return string|null
     */
    private function buildNoteInt(array $bookingData): ?string
    {
        $notes = [];
        
        // Add lodging info
        if ($lodgingName = $bookingData['lodging']['name'] ?? null) {
            $notes[] = "Camera: {$lodgingName}";
        }
        
        // Add sale source
        if ($saleSource = $bookingData['saleSource'] ?? null) {
            $notes[] = "Canale: {$saleSource}";
        }
        
        // Add agency if present
        if ($agencyName = $bookingData['order']['agency']['name'] ?? null) {
            $notes[] = "Agenzia: {$agencyName}";
        }
        
        // Add OTA channel if present
        if ($otaChannel = $bookingData['order']['agency']['otaChannel'] ?? null) {
            $notes[] = "OTA: {$otaChannel}";
        }
        
        return !empty($notes) ? implode(' | ', $notes) : null;
    }

    /**
     * Check if a booking already exists and is up-to-date
     * 
     * @param int $locationId
     * @param string $slopeBookingId UUID
     * @param string|null $lastUpdateDate
     * @return bool
     */
    public function isBookingUpToDate(int $locationId, string $slopeBookingId, ?string $lastUpdateDate): bool
    {
        $existingBooking = SlopeBooking::where('location_id', $locationId)
            ->where('slope_booking_id', $slopeBookingId)
            ->first();

        if (!$existingBooking) {
            return false;
        }

        // If no last_update_date timestamp, consider it not up-to-date
        if (!$lastUpdateDate) {
            return false;
        }

        $lastUpdateAt = $this->parseDateTime($lastUpdateDate);
        
        if (!$lastUpdateAt) {
            return false;
        }

        // Compare timestamps
        return $existingBooking->last_update_date && 
               $existingBooking->last_update_date->equalTo($lastUpdateAt);
    }

    /**
     * Normalize phone number (remove spaces, dots, dashes)
     * Format: 0039 instead of +39 (same as Prenota-Web)
     * 
     * @param string|null $phone
     * @return string|null
     */
    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove spaces, dots, and dashes
        $normalized = preg_replace('/[\s.\-]+/', '', $phone);
        
        // Convert +39 to 0039
        if (str_starts_with($normalized, '+39')) {
            $normalized = '0039' . substr($normalized, 3);
        }
        // If starts with 3 (Italian mobile) and no prefix, add 0039
        elseif (preg_match('/^3\d/', $normalized)) {
            $normalized = '0039' . $normalized;
        }
        // Convert other + prefixes to 00
        elseif (str_starts_with($normalized, '+')) {
            $normalized = '00' . substr($normalized, 1);
        }

        return trim($normalized) ?: null;
    }



    /**
     * Parse datetime string to Carbon instance
     * 
     * @param string|null $datetime
     * @return Carbon|null
     */
    private function parseDateTime(?string $datetime): ?Carbon
    {
        if (!$datetime) {
            return null;
        }

        try {
            return Carbon::parse($datetime);
        } catch (\Exception $e) {
            Log::warning('Failed to parse datetime', [
                'datetime' => $datetime,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send booking to webhook (same format as Prenota-Web)
     * 
     * @param SlopeBooking $booking
     * @param Location $location
     * @return void
     */
    private function sendBookingToWebhook(SlopeBooking $booking, Location $location): void
    {
        $webhookUrl = config('services.leadconnector.slope_webhook');
        
        if (empty($webhookUrl)) {
            Log::info('Slope webhook URL not configured, skipping');
            return;
        }

        try {
            // Formato identico a quello di Prenota-Web
            $payload = [
                'idPrenotazione' => $booking->slope_booking_id,
                'data' => $booking->data ? $booking->data->format('Y-m-d') : null,
                'ora' => $booking->ora ? (is_string($booking->ora) ? $booking->ora : $booking->ora->format('H:i:s')) : null,
                'cliente' => $booking->cliente,
                'telefono' => $booking->telefono,
                'email' => $booking->email,
                'lingua' => $booking->lingua,
                'newsletter' => $booking->newsletter,
                'noteInt' => $booking->note_int,
                'stato' => $booking->stato,
                'situazione' => $booking->situazione,
                'location' => $location->name,
            ];

            Log::info('Sending Slope booking to webhook', [
                'location_id' => $location->id,
                'slope_booking_id' => $booking->slope_booking_id,
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
            ]);

            $response = Http::asJson()->timeout(30)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Slope webhook sent successfully', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $booking->slope_booking_id,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('Slope webhook failed', [
                    'location_id' => $location->id,
                    'slope_booking_id' => $booking->slope_booking_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Slope webhook error', [
                'location_id' => $location->id,
                'slope_booking_id' => $booking->slope_booking_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
