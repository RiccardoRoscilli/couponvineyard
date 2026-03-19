<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\SlopeApiClient;
use App\Services\SlopeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncSlope extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slope:sync
                            {--from= : Start date (Y-m-d format, default: today)}
                            {--to= : End date (Y-m-d format, default: today + 3 days)}
                            {--location= : Filter by location ID}
                            {--dry-run : Log without persisting data}
                            {--test-connection : Test API connectivity only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize bookings from Slope API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = Carbon::now();
        
        // Handle test connection mode
        if ($this->option('test-connection')) {
            return $this->testConnections();
        }

        // Calculate date range - da oggi a +15 giorni
        $daysAhead = config('services.slope.sync_days_ahead', 15);
        $from = $this->option('from') ?: Carbon::today()->toDateString();
        $to = $this->option('to') ?: Carbon::today()->addDays($daysAhead)->toDateString();

        // Create lock key
        $locationId = $this->option('location');
        $lockKey = $locationId 
            ? "slope:sync:location:{$locationId}" 
            : 'slope:sync:all';

        // Try to acquire lock
        $lock = Cache::lock($lockKey, 600); // 10 minutes timeout

        if (!$lock->get()) {
            $this->warn('Another sync process is already running. Exiting.');
            Log::warning('Slope sync skipped - lock already held', [
                'lock_key' => $lockKey,
            ]);
            return self::SUCCESS;
        }

        try {
            return $this->performSync($from, $to, $startTime);
        } finally {
            $lock->release();
        }
    }

    /**
     * Perform the actual sync operation
     */
    private function performSync(string $from, string $to, Carbon $startTime): int
    {

        $this->info("Starting Slope sync: {$from} to {$to}");
        Log::info('Slope sync started', [
            'from' => $from,
            'to' => $to,
            'dry_run' => $this->option('dry-run'),
        ]);

        // Get enabled locations
        $locationsQuery = Location::where('slope_enabled', true);
        
        if ($locationId = $this->option('location')) {
            $locationsQuery->where('id', (int) $locationId);
        }

        $locations = $locationsQuery->get();

        if ($locations->isEmpty()) {
            $this->warn('No Slope-enabled locations found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$locations->count()} location(s)...");

        $overallStats = [
            'total_created' => 0,
            'total_updated' => 0,
            'total_canceled' => 0,
            'total_errors' => 0,
            'locations_processed' => 0,
            'locations_failed' => 0,
        ];

        // Process each location
        foreach ($locations as $location) {
            try {
                $this->processLocation($location, $from, $to, $overallStats);
            } catch (\Exception $e) {
                $overallStats['locations_failed']++;
                $this->error("Location #{$location->id} ({$location->name}): {$e->getMessage()}");
                Log::error('Slope sync failed for location', [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Display summary
        $duration = Carbon::now()->diffInSeconds($startTime);
        
        $this->newLine();
        $this->info('=== Sync Summary ===');
        $this->info("Locations processed: {$overallStats['locations_processed']}");
        $this->info("Locations failed: {$overallStats['locations_failed']}");
        $this->info("Bookings created: {$overallStats['total_created']}");
        $this->info("Bookings updated: {$overallStats['total_updated']}");
        $this->info("Bookings canceled: {$overallStats['total_canceled']}");
        $this->info("Errors: {$overallStats['total_errors']}");
        $this->info("Duration: {$duration} seconds");

        Log::info('Slope sync completed', [
            'stats' => $overallStats,
            'duration_seconds' => $duration,
        ]);

        return $overallStats['locations_failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single location
     */
    private function processLocation(Location $location, string $from, string $to, array &$overallStats): void
    {
        $this->line("Processing: {$location->name} (ID: {$location->id})");

        // Check if location has bearer token
        if (!$location->slope_bearer_token) {
            $this->warn("  ⚠ Skipping: No bearer token configured");
            Log::warning('Location missing Slope bearer token', [
                'location_id' => $location->id,
                'location_name' => $location->name,
            ]);
            return;
        }

        // Create API client and service
        $apiClient = new SlopeApiClient($location->slope_bearer_token);
        $service = new SlopeService($apiClient);

        // Dry run mode - still fetch and process to show what would be synced
        if ($this->option('dry-run')) {
            $this->info("  [DRY RUN] Fetching bookings for {$location->name}");
            
            // Fetch bookings to show what would be synced
            $result = $apiClient->getBookings($from, $to);
            
            if (!$result['success']) {
                $this->error("  ✗ API Error: {$result['error']}");
                return;
            }
            
            $bookingsData = $result['data']['data'] ?? [];
            $this->info("  Found " . count($bookingsData) . " bookings");
            
            // Process and display each booking
            foreach ($bookingsData as $index => $bookingData) {
                $processed = $service->processBooking($bookingData, $location);
                
                if ($processed) {
                    $this->newLine();
                    $this->line("  <fg=cyan>[Booking {$index}]</>");
                    $this->line("  ID: {$processed->slope_booking_id}");
                    $this->line("  Cliente: <fg=yellow>{$processed->cliente}</>");
                    $this->line("  Data: {$processed->data->format('Y-m-d')}");
                    $this->line("  Ora: " . ($processed->ora ? $processed->ora->format('H:i') : 'N/A'));
                    $this->line("  Email: {$processed->email}");
                    $this->line("  Telefono: {$processed->telefono}");
                    $this->line("  Lingua: {$processed->lingua}");
                    $this->line("  Newsletter: {$processed->newsletter}");
                    $this->line("  Ospiti: {$processed->adults} adulti, {$processed->children} bambini");
                    $this->line("  Stato: {$processed->stato} | Situazione: {$processed->situazione}");
                    $this->line("  Note: {$processed->note_int}");
                    
                    // Show webhook payload format
                    $payload = [
                        'idPrenotazione' => $processed->slope_booking_id,
                        'data' => $processed->data->format('Y-m-d'),
                        'ora' => $processed->ora ? $processed->ora->format('H:i:s') : null,
                        'cliente' => $processed->cliente,
                        'telefono' => $processed->telefono,
                        'email' => $processed->email,
                        'lingua' => $processed->lingua,
                        'newsletter' => $processed->newsletter,
                        'noteInt' => $processed->note_int,
                        'stato' => $processed->stato,
                        'situazione' => $processed->situazione,
                        'location' => $location->name,
                    ];
                    
                    $this->line("  <fg=green>Webhook Payload:</>");
                    $this->line("  " . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            }
            
            Log::info('Slope sync dry-run', [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'from' => $from,
                'to' => $to,
                'bookings_found' => count($bookingsData),
            ]);
            return;
        }

        // Sync bookings (includes both active and canceled bookings)
        $this->line("  → Syncing bookings...");
        $stats = $service->syncBookingsForLocation($location, $from, $to);

        // Update overall stats
        $overallStats['locations_processed']++;
        $overallStats['total_created'] += $stats['created'];
        $overallStats['total_updated'] += $stats['updated'];
        $overallStats['total_canceled'] += $stats['canceled'];
        $overallStats['total_errors'] += count($stats['errors']);

        // Display location results
        $totalErrors = count($stats['errors']);
        
        if ($totalErrors > 0) {
            $this->warn("  ⚠ Created: {$stats['created']}, Updated: {$stats['updated']}, Canceled: {$stats['canceled']}, Errors: {$totalErrors}");
            foreach ($stats['errors'] as $error) {
                $this->error("    - {$error}");
            }
        } else {
            $this->info("  ✓ Created: {$stats['created']}, Updated: {$stats['updated']}, Canceled: {$stats['canceled']}");
        }

        Log::info('Slope sync completed for location', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'stats' => $stats,
        ]);
    }

    /**
     * Test API connections for all enabled locations
     */
    private function testConnections(): int
    {
        $this->info('Testing Slope API connections...');

        $locations = Location::where('slope_enabled', true)->get();

        if ($locations->isEmpty()) {
            $this->warn('No Slope-enabled locations found.');
            return self::SUCCESS;
        }

        $allSuccess = true;

        foreach ($locations as $location) {
            $this->line("Testing: {$location->name} (ID: {$location->id})");

            if (!$location->slope_bearer_token) {
                $this->error("  ✗ No bearer token configured");
                $allSuccess = false;
                continue;
            }

            $apiClient = new SlopeApiClient($location->slope_bearer_token);
            
            if ($apiClient->testConnection()) {
                $this->info("  ✓ Connection successful");
            } else {
                $this->error("  ✗ Connection failed");
                $allSuccess = false;
            }
        }

        return $allSuccess ? self::SUCCESS : self::FAILURE;
    }
}
