<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['school_id' => School::factory(), 'employee_code' => fake()->unique()->bothify('T####'), 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'joining_date' => '2026-01-01', 'status' => 'active'];
    }
}
