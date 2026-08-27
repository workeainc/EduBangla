<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */ class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'student_code' => fake()->unique()->bothify('S####'), 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'admission_date' => '2026-01-01', 'status' => 'active'];
    }
}
