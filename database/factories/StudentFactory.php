<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(), 
            'lrn' => fake()->unique()->numerify('############'), // 12-digit LRN
            'school_year' => '2026-2027',
            'is_manually_edited' => false,
            'sex' => fake()->randomElement(['Male', 'Female']),
            'age' => 16,
            'place_of_birth' => fake()->city(),
            'mother_tongue' => 'Tagalog',
        ];
    }
}