<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-' . fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('##########'),
            'phone' => '05' . fake()->numerify('########'),
            'hire_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'employee_type' => 'guard',
            'job_title' => 'حارس أمن',
            'status' => 'active',
            'base_salary' => fake()->randomElement([3000, 3200, 3500, 4000]),
            'housing_allowance' => 0,
            'transport_allowance' => 0,
            'food_allowance' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'terminated']);
    }

    public function withSalary(float $baseSalary, float $housing = 0, float $transport = 0): static
    {
        return $this->state(fn(array $attributes) => [
            'base_salary' => $baseSalary,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
        ]);
    }
}
