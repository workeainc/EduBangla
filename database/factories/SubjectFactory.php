<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */ class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'name' => 'Mathematics', 'code' => 'MATH', 'short_name' => 'Math', 'status' => 'active'];
    }
}
