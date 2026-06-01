<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),

            'date' => now()->toDateString(),

            'status' => 'present',

            'is_late' => false,

            'overtime' => 0,
        ];
    }
}