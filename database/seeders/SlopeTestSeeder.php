<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\SlopeBooking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SlopeTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test locations with Slope enabled
        $location1 = Location::updateOrCreate(
            ['name' => 'Villa Crespi Test'],
            [
                'suffix' => 'VC',
                'ipratico_key' => '20154:test-key-1',
                'slope_bearer_token' => 'f4a3ef9d38fdb46b1c6533260291a4b6', // Test API key
                'slope_enabled' => true,
            ]
        );

        $location2 = Location::updateOrCreate(
            ['name' => 'Laqua Countryside Test'],
            [
                'suffix' => 'LC',
                'ipratico_key' => '20155:test-key-2',
                'slope_bearer_token' => 'test-token-' . Str::random(32),
                'slope_enabled' => false, // Disabled for testing
            ]
        );

        $this->command->info("Created test locations:");
        $this->command->info("  - {$location1->name} (ID: {$location1->id}) - Slope ENABLED");
        $this->command->info("  - {$location2->name} (ID: {$location2->id}) - Slope DISABLED");

        // Create sample bookings for location 1
        $bookings = [
            [
                'slope_booking_id' => Str::uuid(),
                'data' => Carbon::today()->addDays(2),
                'ora' => '15:00:00',
                'cliente' => 'Mario Rossi',
                'telefono' => '+393331234567',
                'email' => 'mario.rossi@example.com',
                'lingua' => 'it',
                'newsletter' => 'S',
                'note_int' => 'Camera: Suite Deluxe | Canale: DIRECT',
                'stato' => 1, // Confermata
                'situazione' => 1, // Normale
                'departure_date' => Carbon::today()->addDays(5),
                'adults' => 2,
                'children' => 0,
                'is_canceled' => false,
            ],
            [
                'slope_booking_id' => Str::uuid(),
                'data' => Carbon::today()->addDays(3),
                'ora' => '16:00:00',
                'cliente' => 'Laura Bianchi',
                'telefono' => '+393347654321',
                'email' => 'laura.bianchi@example.com',
                'lingua' => 'it',
                'newsletter' => 'N',
                'note_int' => 'Camera: Standard | Canale: BOOKING | OTA: BOOKING_COM',
                'stato' => 2, // Opzione
                'situazione' => 1, // Normale
                'departure_date' => Carbon::today()->addDays(4),
                'adults' => 2,
                'children' => 1,
                'is_canceled' => false,
            ],
            [
                'slope_booking_id' => Str::uuid(),
                'data' => Carbon::today()->subDays(2),
                'ora' => '14:00:00',
                'cliente' => 'John Smith',
                'telefono' => '+441234567890',
                'email' => 'john.smith@example.com',
                'lingua' => 'en',
                'newsletter' => 'S',
                'note_int' => 'Camera: Junior Suite | Canale: EXPEDIA | OTA: EXPEDIA',
                'stato' => 3, // Cancellata
                'situazione' => 1, // Normale
                'departure_date' => Carbon::today(),
                'adults' => 2,
                'children' => 0,
                'is_canceled' => true,
            ],
        ];

        foreach ($bookings as $bookingData) {
            SlopeBooking::updateOrCreate(
                [
                    'location_id' => $location1->id,
                    'slope_booking_id' => $bookingData['slope_booking_id'],
                ],
                array_merge($bookingData, [
                    'location_id' => $location1->id,
                    'last_update_date' => Carbon::now(),
                    'synced_at' => Carbon::now(),
                ])
            );
        }

        $this->command->info("Created " . count($bookings) . " sample bookings for {$location1->name}");
        $this->command->info("\nYou can now test the Slope sync with:");
        $this->command->info("  php artisan slope:sync --dry-run");
        $this->command->info("  php artisan slope:sync --test-connection");
        $this->command->info("  php artisan slope:sync --location={$location1->id}");
    }
}
