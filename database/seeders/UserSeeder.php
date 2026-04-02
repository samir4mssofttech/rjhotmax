<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'System Admin',
            'email' => 'admin@rjhotmax.app',
            'password' => 'password',
            'user_role' => UserRole::ADMIN,
        ]);
    }
}