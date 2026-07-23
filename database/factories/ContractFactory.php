<?php

namespace Database\Factories;

use App\Models\ClientCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_company_id' => ClientCompany::factory(),
            'contract_number' => 'CNT-TEST-' . fake()->unique()->numerify('####'),
            'contract_type' => 'monthly',
            'billing_cycle' => 'monthly',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'auto_renew' => false,
            'status' => 'active',
            'payment_terms' => 'الدفع خلال 15 يوم من تاريخ الفاتورة',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'draft']);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn(array $attributes) => ['end_date' => now()->addDays(10)]);
    }
}
