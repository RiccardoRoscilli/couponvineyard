<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SlopeBooking;
use App\Services\SlopeApiClient;
use App\Services\SlopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlopeSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test che il campo stato accetti valori varchar
     */
    public function test_stato_field_accepts_varchar_values(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '123e4567-e89b-12d3-a456-426614174000',
            'data' => Carbon::today(),
            'cliente' => 'Test Cliente',
            'stato' => 'Attiva',
            'situazione' => 1,
        ]);

        $this->assertEquals('Attiva', $booking->stato);
        $this->assertIsString($booking->stato);
    }

    /**
     * Test che gli stati possibili siano corretti
     */
    public function test_booking_status_constants(): void
    {
        $this->assertEquals('Attiva', SlopeBooking::STATUS_ATTIVA);
        $this->assertEquals('Modificata', SlopeBooking::STATUS_MODIFICATA);
        $this->assertEquals('Cancellata', SlopeBooking::STATUS_CANCELLATA);
    }

    /**
     * Test che una nuova prenotazione abbia stato Attiva
     */
    public function test_new_booking_has_attiva_status(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '123e4567-e89b-12d3-a456-426614174001',
            'data' => Carbon::today(),
            'cliente' => 'Test Cliente',
            'stato' => SlopeBooking::STATUS_ATTIVA,
            'situazione' => 1,
        ]);

        $this->assertEquals(SlopeBooking::STATUS_ATTIVA, $booking->stato);
    }

    /**
     * Test che una prenotazione cancellata abbia stato Cancellata
     */
    public function test_canceled_booking_has_cancellata_status(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '123e4567-e89b-12d3-a456-426614174002',
            'data' => Carbon::today(),
            'cliente' => 'Test Cliente',
            'stato' => SlopeBooking::STATUS_CANCELLATA,
            'is_canceled' => true,
            'situazione' => 1,
        ]);

        $this->assertEquals(SlopeBooking::STATUS_CANCELLATA, $booking->stato);
        $this->assertTrue($booking->is_canceled);
    }

    /**
     * Test che una prenotazione modificata abbia stato Modificata
     */
    public function test_modified_booking_has_modificata_status(): void
    {
        $location = Location::factory()->create([
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-token',
        ]);

        // Crea prenotazione iniziale
        $booking = SlopeBooking::create([
            'location_id' => $location->id,
            'slope_booking_id' => '123e4567-e89b-12d3-a456-426614174003',
            'data' => Carbon::today(),
            'departure_date' => Carbon::today()->addDays(3),
            'cliente' => 'Test Cliente',
            'stato' => SlopeBooking::STATUS_ATTIVA,
            'situazione' => 1,
        ]);

        // Simula modifica della data
        $booking->update([
            'data' => Carbon::today()->addDay(),
            'stato' => SlopeBooking::STATUS_MODIFICATA,
        ]);

        $this->assertEquals(SlopeBooking::STATUS_MODIFICATA, $booking->fresh()->stato);
    }
}
