<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CashAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'صندوق تجريبي - ' . fake()->word(),
            'type' => 'cash',
            'opening_balance' => 20000,
            'current_balance' => 20000,
            'is_active' => true,
        ];
    }

    public function withBalance(float $balance): static
    {
        return $this->state(fn(array $attributes) => [
            'opening_balance' => $balance,
            'current_balance' => $balance,
        ]);
    }
}
