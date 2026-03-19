<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SlopeBooking;
use App\Services\SlopeApiClient;
use App\Services\SlopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlopeCancellationCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test checking recent bookings for cancellations
     */
    public function test_check_recent_cancellations_marks_deleted_bookings_as_canceled(): void
    {
        // Create a test location
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        // Create some recent bookings (last 10 days) that are NOT canceled
        $booking1 = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
            'data' => Carbon::now()->subDays(5),
            'cliente' => 'Mario Rossi',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        $booking2 = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'data' => Carbon::now()->subDays(3),
            'cliente' => 'Luigi Verdi',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Create an old booking (should not be checked)
        $oldBooking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => 'old-booking-id',
            'data' => Carbon::now()->subDays(20),
            'cliente' => 'Giovanni Bianchi',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Create an already canceled booking (should not be checked)
        $canceledBooking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => 'canceled-booking-id',
            'data' => Carbon::now()->subDays(2),
            'cliente' => 'Paolo Neri',
            'stato' => 'Cancellata',
            'is_canceled' => true,
            'synced_at' => Carbon::now(),
        ]);

        // Mock the API response - booking1 is deleted, booking2 is not
        Http::fake([
            '*/v1/deleted-resources' => Http::response([
                'data' => [
                    [
                        'resourceId' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
                        'deletedAt' => Carbon::now()->toIso8601String(),
                    ],
                ],
            ], 200),
        ]);

        // Create service and check cancellations
        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $stats = $service->checkRecentCancellations($location, 15);

        // Assert stats
        $this->assertEquals(2, $stats['checked']); // Only 2 non-canceled recent bookings
        $this->assertEquals(1, $stats['canceled']); // Only booking1 was marked as canceled
        $this->assertEmpty($stats['errors']);

        // Refresh bookings from database
        $booking1->refresh();
        $booking2->refresh();
        $oldBooking->refresh();
        $canceledBooking->refresh();

        // Assert booking1 is now canceled
        $this->assertEquals('Cancellata', $booking1->stato);
        $this->assertTrue($booking1->is_canceled);

        // Assert booking2 is still active
        $this->assertEquals('Attiva', $booking2->stato);
        $this->assertFalse($booking2->is_canceled);

        // Assert old booking was not checked
        $this->assertEquals('Attiva', $oldBooking->stato);

        // Assert already canceled booking remains canceled
        $this->assertEquals('Cancellata', $canceledBooking->stato);
    }

    /**
     * Test that empty response doesn't cause errors
     */
    public function test_check_recent_cancellations_handles_empty_response(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        // Create a recent booking
        SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => 'test-booking-id',
            'data' => Carbon::now()->subDays(5),
            'cliente' => 'Test User',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Mock empty API response (no cancellations)
        Http::fake([
            '*/v1/deleted-resources' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $stats = $service->checkRecentCancellations($location, 15);

        $this->assertEquals(1, $stats['checked']);
        $this->assertEquals(0, $stats['canceled']);
        $this->assertEmpty($stats['errors']);
    }

    /**
     * Test that API errors are handled gracefully
     */
    public function test_check_recent_cancellations_handles_api_errors(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        // Create a recent booking
        SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => 'test-booking-id',
            'data' => Carbon::now()->subDays(5),
            'cliente' => 'Test User',
            'stato' => 'Attiva',
            'is_canceled' => false,
            'synced_at' => Carbon::now(),
        ]);

        // Mock API error
        Http::fake([
            '*/v1/deleted-resources' => Http::response([], 500),
        ]);

        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $stats = $service->checkRecentCancellations($location, 15);

        $this->assertEquals(1, $stats['checked']);
        $this->assertEquals(0, $stats['canceled']);
        $this->assertNotEmpty($stats['errors']);
    }

    /**
     * Test that no bookings to check returns early
     */
    public function test_check_recent_cancellations_with_no_bookings(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        // Don't create any bookings

        // Should not make any API calls
        Http::fake();

        $apiClient = new SlopeApiClient('test-token', 'https://api.test.slope.it');
        $service = new SlopeService($apiClient);
        
        $stats = $service->checkRecentCancellations($location, 15);

        $this->assertEquals(0, $stats['checked']);
        $this->assertEquals(0, $stats['canceled']);
        $this->assertEmpty($stats['errors']);

        // Verify no HTTP requests were made
        Http::assertNothingSent();
    }
}
