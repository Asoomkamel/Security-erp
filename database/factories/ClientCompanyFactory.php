<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientCompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => '05' . fake()->numerify('########'),
            'is_active' => true,
            'credit_limit' => 0,
            'payment_terms_days' => 30,
        ];
    }

    public function withCreditLimit(float $limit): static
    {
        return $this->state(fn(array $attributes) => ['credit_limit' => $limit]);
    }
}
