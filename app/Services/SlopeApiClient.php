<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Slope API Client
 * 
 * Handles all HTTP communication with the Slope API.
 * Manages authentication, request formatting, and error handling.
 * 
 * @example
 * $client = new SlopeApiClient('your-bearer-token');
 * $result = $client->getBookings('2025-01-01', '2025-01-31');
 */
class SlopeApiClient
{
    private string $bearerToken;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;
    private int $retryDelay;

    /**
     * Create a new Slope API client instance
     * 
     * @param string $bearerToken Bearer token for authentication
     * @param string|null $baseUrl Optional base URL override (defaults to config)
     */
    public function __construct(string $bearerToken, ?string $baseUrl = null)
    {
        $this->bearerToken = $bearerToken;
        $this->baseUrl = rtrim($baseUrl ?? config('services.slope.base_url', 'https://api.staging.slope.it'), '/');
        $this->timeout = config('services.slope.timeout', 60);
        $this->retryAttempts = config('services.slope.retry_attempts', 3);
        $this->retryDelay = config('services.slope.retry_delay', 1);
    }

    /**
     * Fetch bookings for a date range
     * 
     * @param string $from Start date in Y-m-d format
     * @param string $to End date in Y-m-d format
     * @return array{success: bool, data: array|null, error: string|null, status: int|null}
     */
    public function getBookings(string $from, string $to): array
    {
        return $this->makeRequestWithRetry(function () use ($from, $to) {
            // Build filter parameter: stayPeriod.arrival:ge:FROM,stayPeriod.arrival:le:TO
            $filter = "stayPeriod.arrival:ge:{$from},stayPeriod.arrival:le:{$to}";
            $url = "{$this->baseUrl}/v1/lodging-reservations";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($url, [
                'filter' => $filter,
                'expand' => 'primaryGuest,primaryGuest.primaryEmail,primaryGuest.primaryPhoneNumber,order.customer,order.customer.primaryEmail,order.customer.primaryPhoneNumber,lodging,lodgingType,order.agency',
            ]);

            return ['response' => $response, 'url' => $url];
        });
    }

    /**
     * Make HTTP request with retry logic for server errors
     */
    private function makeRequestWithRetry(callable $requestCallback): array
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                $result = $requestCallback();
                $response = $result['response'];
                $url = $result['url'];

                $status = $response->status();

                // Handle authentication errors (no retry)
                if ($status === 401) {
                    Log::error('Slope API authentication failed', [
                        'url' => $url,
                        'status' => $status,
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Authentication failed: Invalid or expired bearer token',
                        'status' => $status,
                    ];
                }

                // Handle not found (no retry)
                if ($status === 404) {
                    Log::warning('Slope API endpoint not found', [
                        'url' => $url,
                        'status' => $status,
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => 'Endpoint not found or no bookings available',
                        'status' => $status,
                    ];
                }

                // Handle server errors (with retry)
                if ($status >= 500) {
                    $errorMsg = "Server error: HTTP {$status}";
                    
                    Log::error('Slope API server error', [
                        'url' => $url,
                        'status' => $status,
                        'attempt' => $attempt,
                        'max_attempts' => $this->retryAttempts,
                        'body' => $response->body(),
                    ]);
                    
                    // Retry with exponential backoff
                    if ($attempt < $this->retryAttempts) {
                        $delay = $this->retryDelay * pow(2, $attempt - 1);
                        Log::info("Retrying after {$delay} seconds...");
                        sleep($delay);
                        continue;
                    }
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => $errorMsg . " (after {$attempt} attempts)",
                        'status' => $status,
                    ];
                }

                // Handle other client errors (no retry)
                if (!$response->successful()) {
                    Log::warning('Slope API client error', [
                        'url' => $url,
                        'status' => $status,
                        'body' => $response->body(),
                    ]);
                    
                    return [
                        'success' => false,
                        'data' => null,
                        'error' => "Client error: HTTP {$status}",
                        'status' => $status,
                    ];
                }

                // Success
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data,
                    'error' => null,
                    'status' => $status,
                ];

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                
                Log::error('Slope API connection error', [
                    'url' => $url ?? 'unknown',
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                
                // Retry network errors once
                if ($attempt < min(2, $this->retryAttempts)) {
                    Log::info("Retrying after network error (attempt {$attempt})...");
                    sleep(5);
                    continue;
                }
                
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Network error: ' . $e->getMessage(),
                    'status' => null,
                ];
            } catch (\Illuminate\Http\Client\RequestException $e) {
                Log::error('Slope API request error', [
                    'url' => $url ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Request error: ' . $e->getMessage(),
                    'status' => $e->response?->status(),
                ];
            } catch (\Exception $e) {
                Log::error('Slope API unexpected error', [
                    'url' => $url ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Unexpected error: ' . $e->getMessage(),
                    'status' => null,
                ];
            }
        }
        
        // Should never reach here, but just in case
        return [
            'success' => false,
            'data' => null,
            'error' => 'Max retry attempts reached',
            'status' => null,
        ];
    }

    /**
     * Fetch deleted resources (canceled bookings)
     * 
     * @param string $from Start date in Y-m-d format
     * @param string $to End date in Y-m-d format
     * @return array{success: bool, data: array|null, error: string|null, status: int|null}
     */
    public function getDeletedBookings(string $from, string $to): array
    {
        return $this->makeRequestWithRetry(function () use ($from, $to) {
            $url = "{$this->baseUrl}/v1/deleted-resources";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($url, [
                'type' => 'LODGING_RESERVATIONS',
                'from' => $from,
                'to' => $to,
            ]);

            return ['response' => $response, 'url' => $url];
        });
    }

    /**
     * Check if specific bookings have been deleted/canceled
     * 
     * @param array $bookingIds Array of Slope booking IDs (UUIDs)
     * @return array{success: bool, data: array|null, error: string|null, status: int|null}
     */
    public function checkDeletedBookings(array $bookingIds): array
    {
        return $this->makeRequestWithRetry(function () use ($bookingIds) {
            $url = "{$this->baseUrl}/v1/deleted-resources";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($url, [
                'ids' => $bookingIds,
                'type' => 'LODGING_RESERVATIONS',
            ]);

            return ['response' => $response, 'url' => $url];
        });
    }

    /**
     * Get a single booking by ID
     * 
     * @param string $bookingId The Slope booking ID (UUID)
     * @return array{success: bool, data: array|null, error: string|null, status: int|null}
     */
    public function getBooking(string $bookingId): array
    {
        $url = "{$this->baseUrl}/v1/lodging-reservations/{$bookingId}";
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->get($url);

            $status = $response->status();

            if ($status === 401) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Authentication failed: Invalid or expired bearer token',
                    'status' => $status,
                ];
            }

            if ($status === 404) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Booking not found',
                    'status' => $status,
                ];
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => "HTTP error: {$status}",
                    'status' => $status,
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'error' => null,
                'status' => $status,
            ];

        } catch (\Exception $e) {
            Log::error('Slope API error fetching booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'status' => null,
            ];
        }
    }

    /**
     * Test API connection and authentication
     * 
     * @return bool True if connection is successful and authenticated
     */
    public function testConnection(): bool
    {
        try {
            // Test with a simple query for today's reservations
            $today = date('Y-m-d');
            $filter = "stayPeriod.arrival:ge:{$today},stayPeriod.arrival:le:{$today}";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->get("{$this->baseUrl}/v1/lodging-reservations", [
                'filter' => $filter,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Slope API connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
