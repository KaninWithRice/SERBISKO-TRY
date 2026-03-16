<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => null,
            'extension_name' => null,
            'birthday' => fake()->date('Y-m-d', '2008-01-01'), 
            'password' => static::$password ??= \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin', // Default for tests, or 'student'
            'password_changed_at' => null,
            // email, email_verified_at, and remember_token are REMOVED
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
