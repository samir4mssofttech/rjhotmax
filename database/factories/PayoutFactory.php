<?php

namespace Database\Factories;

use App\Models\Payout;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\PayoutStatus;

class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'branch_id' => Branch::factory(),

            'payout_month' => now()->format('Y-m'),

            'status' => PayoutStatus::Draft,

            'net_salary' => 10000,
        ];
    }
}