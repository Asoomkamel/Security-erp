<?php

namespace Database\Factories;

use App\Models\ClientCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesInvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_company_id' => ClientCompany::factory(),
            'invoice_number' => 'INV-TEST-' . fake()->unique()->numerify('####'),
            'invoice_date' => now(),
            'due_date' => now()->addDays(15),
            'tax_rate' => 15,
            'status' => 'unpaid',
            'source' => 'manual',
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'invoice_date' => now()->subDays(30),
            'due_date' => now()->subDays(15),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'paid']);
    }
}
