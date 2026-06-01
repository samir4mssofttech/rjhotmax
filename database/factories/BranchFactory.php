<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test Branch',
            'code' => 'BR001',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}