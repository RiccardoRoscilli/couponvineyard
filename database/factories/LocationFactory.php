<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slope_enabled' => false,
            'slope_bearer_token' => null,
        ];
    }

    public function slopeEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'slope_enabled' => true,
            'slope_bearer_token' => 'test-bearer-token-' . $this->faker->uuid(),
        ]);
    }
}
