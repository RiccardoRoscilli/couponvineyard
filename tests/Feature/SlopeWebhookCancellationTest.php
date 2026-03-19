<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SlopeBooking;
use App\Services\SlopeApiClient;
use App\Services\SlopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlopeWebhookCancellationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that webhook is called for canceled bookings
     */
    public function test_webhook_is_called_for_canceled_bookings(): void
    {
        // Set webhook URL
        Config::set('services.leadconnector.slope_webhook', 'https://webhook.test/slope');

        // Create a test location
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
            'name' => 'Test Hotel',
        ]);

        // Create a booking that will be canceled
        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
            'data' => Carbon::now()->subDays(5),
            'cliente' => 'Mario Rossi',
            'email' => 'mario@test.com',
            'telefono' => '00393331234567',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Mock the API response - booking is deleted
        Http::fake([
            '*/v1/deleted-resources' => Http::response([
                'data' => [
                    [
                        'resourceId' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
                        'deletedAt' => Carbon::now()->toIso8601String(),
                    ],
                ],
            ], 200),
            'https://webhook.test/slope' => Http::response([], 200),
        ]);

        // Create service and check cancellations
        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $stats = $service->checkRecentCancellations($location, 15);

        // Assert booking was canceled
        $this->assertEquals(1, $stats['canceled']);

        // Assert webhook was called
        Http::assertSent(function ($request) use ($booking, $location) {
            return $request->url() === 'https://webhook.test/slope' &&
                   $request['idPrenotazione'] === $booking->slope_booking_id &&
                   $request['stato'] === 'Cancellata' &&
                   $request['cliente'] === 'Mario Rossi' &&
                   $request['location'] === 'Test Hotel';
        });
    }

    /**
     * Test that webhook is called for canceled bookings via syncCanceledBookingsForLocation
     */
    public function test_webhook_is_called_for_canceled_bookings_via_sync(): void
    {
        // Set webhook URL
        Config::set('services.leadconnector.slope_webhook', 'https://webhook.test/slope');

        // Create a test location
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
            'name' => 'Test Hotel',
        ]);

        // Create a booking that will be canceled
        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
            'data' => Carbon::now()->subDays(5),
            'cliente' => 'Luigi Verdi',
            'email' => 'luigi@test.com',
            'telefono' => '00393331234567',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Mock the API response - booking is deleted
        Http::fake([
            '*/v1/deleted-resources' => Http::response([
                'data' => [
                    [
                        'resourceId' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
                        'deletedAt' => Carbon::now()->toIso8601String(),
                    ],
                ],
            ], 200),
            'https://webhook.test/slope' => Http::response([], 200),
        ]);

        // Create service and sync canceled bookings
        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $from = Carbon::now()->subDays(15)->format('Y-m-d');
        $to = Carbon::now()->format('Y-m-d');
        
        $stats = $service->syncCanceledBookingsForLocation($location, $from, $to);

        // Assert booking was canceled
        $this->assertEquals(1, $stats['canceled']);

        // Assert webhook was called
        Http::assertSent(function ($request) use ($booking, $location) {
            return $request->url() === 'https://webhook.test/slope' &&
                   $request['idPrenotazione'] === $booking->slope_booking_id &&
                   $request['stato'] === 'Cancellata' &&
                   $request['cliente'] === 'Luigi Verdi' &&
                   $request['location'] === 'Test Hotel';
        });
    }

    /**
     * Test that webhook is called for all booking states (Attiva, Modificata, Cancellata)
     */
    public function test_webhook_is_called_for_all_booking_states(): void
    {
        // Set webhook URL
        Config::set('services.leadconnector.slope_webhook', 'https://webhook.test/slope');

        // Create a test location
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
            'name' => 'Test Hotel',
        ]);

        // Mock API responses
        Http::fake([
            '*/v1/lodging-reservations*' => Http::response([
                'data' => [
                    // New booking (Attiva)
                    [
                        'id' => 'new-booking-id',
                        'stayPeriod' => [
                            'arrival' => '2025-12-01',
                            'departure' => '2025-12-05',
                        ],
                        'primaryGuest' => [
                            'firstName' => 'Giovanni',
                            'lastName' => 'Bianchi',
                            'primaryEmail' => ['address' => 'giovanni@test.com'],
                        ],
                        'guestCounts' => ['adults' => 2, 'children' => 0],
                        'isCanceled' => false,
                    ],
                    // Canceled booking
                    [
                        'id' => 'canceled-booking-id',
                        'stayPeriod' => [
                            'arrival' => '2025-12-10',
                            'departure' => '2025-12-15',
                        ],
                        'primaryGuest' => [
                            'firstName' => 'Paolo',
                            'lastName' => 'Neri',
                            'primaryEmail' => ['address' => 'paolo@test.com'],
                        ],
                        'guestCounts' => ['adults' => 1, 'children' => 1],
                        'isCanceled' => true,
                    ],
                ],
                'pagination' => ['hasNextPage' => false],
            ], 200),
            'https://webhook.test/slope' => Http::response([], 200),
        ]);

        // Create service and sync bookings
        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $from = Carbon::now()->format('Y-m-d');
        $to = Carbon::now()->addDays(30)->format('Y-m-d');
        
        $stats = $service->syncBookingsForLocation($location, $from, $to);

        // Assert bookings were created
        $this->assertEquals(2, $stats['created']);
        $this->assertEquals(1, $stats['canceled']);

        // Assert webhook was called for both bookings (including canceled one)
        // Note: We expect 3 calls total - 1 for API request + 2 for webhooks
        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test/slope';
        });
        
        // Check Attiva booking webhook
        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test/slope' &&
                   $request['idPrenotazione'] === 'new-booking-id' &&
                   $request['stato'] === 'Attiva' &&
                   $request['cliente'] === 'Giovanni Bianchi';
        });
        
        // Check Cancellata booking webhook
        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test/slope' &&
                   $request['idPrenotazione'] === 'canceled-booking-id' &&
                   $request['stato'] === 'Cancellata' &&
                   $request['cliente'] === 'Paolo Neri';
        });
    }
}
