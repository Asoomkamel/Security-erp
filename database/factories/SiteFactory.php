<?php

namespace Database\Factories;

use App\Models\ClientCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_company_id' => ClientCompany::factory(),
            'name' => fake()->streetName() . ' - موقع الحراسة',
            'address' => fake()->address(),
            'required_guards_count' => fake()->numberBetween(2, 6),
            'is_active' => true,
            'geofence_radius_meters' => 150,
        ];
    }

    public function withCoordinates(float $lat, float $lng): static
    {
        return $this->state(fn(array $attributes) => ['latitude' => $lat, 'longitude' => $lng]);
    }
}
