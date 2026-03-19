<?php

namespace Tests\Helpers;

use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Test data generators for Slope API property-based testing
 */
class SlopeTestGenerators
{
    /**
     * Generate a random Location with Slope credentials
     */
    public static function generateLocation(): Location
    {
        return Location::factory()->create([
            'slope_bearer_token' => self::generateBearerToken(),
            'slope_enabled' => true,
        ]);
    }

    /**
     * Generate a random bearer token
     */
    public static function generateBearerToken(): string
    {
        return Str::random(32);
    }

    /**
     * Generate random Slope booking data matching API format
     */
    public static function generateSlopeBookingData(array $overrides = []): array
    {
        $baseData = [
            'id' => rand(1000, 999999),
            'date' => Carbon::today()->addDays(rand(0, 30))->toDateString(),
            'time' => sprintf('%02d:%02d:00', rand(10, 22), rand(0, 59)),
            'customer_name' => fake()->name(),
            'customer_email' => rand(0, 1) ? fake()->email() : null,
            'customer_phone' => self::generatePhoneNumber(),
            'customer_language' => fake()->randomElement(['it', 'en', 'fr', 'de', null]),
            'party_size' => rand(1, 12),
            'status' => rand(1, 5),
            'situation' => rand(1, 3),
            'notes' => rand(0, 1) ? fake()->sentence() : null,
            'newsletter' => fake()->randomElement(['yes', 'no', 'Y', 'N', 'S', true, false, null]),
            'last_modified' => Carbon::now()->subMinutes(rand(0, 1440))->toIso8601String(),
        ];

        return array_merge($baseData, $overrides);
    }

    /**
     * Generate random phone number (with various formats)
     */
    public static function generatePhoneNumber(): ?string
    {
        $formats = [
            '+39' . rand(300, 399) . rand(1000000, 9999999), // Italian mobile with prefix
            '3' . rand(10, 99) . rand(1000000, 9999999),     // Italian mobile without prefix
            '+1' . rand(200, 999) . rand(1000000, 9999999),  // US number
            null,                                             // Missing phone
        ];

        return fake()->randomElement($formats);
    }

    /**
     * Generate random date range
     */
    public static function generateDateRange(): array
    {
        $from = Carbon::today()->addDays(rand(-30, 0));
        $to = $from->copy()->addDays(rand(1, 30));

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * Generate random status code
     */
    public static function generateStatusCode(): int
    {
        return fake()->randomElement([1, 2, 3, 4, 5]);
    }

    /**
     * Generate booking data with missing required fields (edge case)
     */
    public static function generateIncompleteBookingData(): array
    {
        $data = self::generateSlopeBookingData();
        
        // Randomly remove required fields
        $requiredFields = ['id', 'date', 'time', 'customer_name'];
        $fieldToRemove = fake()->randomElement($requiredFields);
        unset($data[$fieldToRemove]);

        return $data;
    }

    /**
     * Generate booking data with all optional fields missing (edge case)
     */
    public static function generateMinimalBookingData(): array
    {
        return [
            'id' => rand(1000, 999999),
            'date' => Carbon::today()->toDateString(),
            'time' => '19:00:00',
            'customer_name' => fake()->name(),
        ];
    }

    /**
     * Generate multiple locations with different tokens
     */
    public static function generateMultipleLocations(int $count = 3): array
    {
        $locations = [];
        
        for ($i = 0; $i < $count; $i++) {
            $locations[] = self::generateLocation();
        }

        return $locations;
    }

    /**
     * Generate API response with bookings
     */
    public static function generateApiResponse(int $bookingCount = 5): array
    {
        $bookings = [];
        
        for ($i = 0; $i < $bookingCount; $i++) {
            $bookings[] = self::generateSlopeBookingData();
        }

        return [
            'success' => true,
            'bookings' => $bookings,
            'total' => $bookingCount,
        ];
    }

    /**
     * Generate edge case: empty response
     */
    public static function generateEmptyApiResponse(): array
    {
        return [
            'success' => true,
            'bookings' => [],
            'total' => 0,
        ];
    }

    /**
     * Generate edge case: malformed date/time
     */
    public static function generateBookingWithMalformedDateTime(): array
    {
        return self::generateSlopeBookingData([
            'date' => 'invalid-date',
            'time' => '25:99:99',
        ]);
    }

    /**
     * Generate edge case: special characters in customer data
     */
    public static function generateBookingWithSpecialCharacters(): array
    {
        return self::generateSlopeBookingData([
            'customer_name' => "O'Brien-Smith <test@example.com>",
            'customer_email' => 'test+special@example.com',
            'notes' => "Special chars: <>&\"'",
        ]);
    }
}
