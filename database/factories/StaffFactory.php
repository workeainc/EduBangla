<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['school_id' => School::factory(), 'employee_code' => fake()->unique()->bothify('ST####'), 'name' => fake()->name(), 'designation' => 'Office Staff', 'joining_date' => '2026-01-01', 'status' => 'active'];
    }
}
