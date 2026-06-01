<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),

            'name' => fake()->name(),

            'designation' => $this->faker->jobTitle(), // <--- Add this

            'join_date' => now()->subMonths(6),

            'payout_type' => 'salaried',

            'basic_salary' => 3000000,
            'hra' => 1000000,
            'conveyance' => 500000,
            'medical' => 200000,
            'other_allowances' => 300000,

            'pf' => 180000,
            'esi' => 75000,

            'is_active' => true,
        ];
    }
}
