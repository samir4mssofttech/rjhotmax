<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\Department;
use App\Enums\Designation;
use App\Enums\Gender;
use App\Enums\UserRole;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_role' => $this->faker->randomElement(UserRole::class),
            'employee_code' => $this->faker->unique()->bothify('EMP-######'),
            'department' => $this->faker->randomElement(Department::class),
            'designation' => $this->faker->randomElement(Designation::class),
            'mobile_number' => $this->faker->phoneNumber(),
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(Gender::class),
            'aadhaar_number' => $this->faker->unique()->numerify('############'),
            'pan_number' => Str::upper($this->faker->unique()->bothify('?????####?')), // capital letters
            'state' => $this->faker->city(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
