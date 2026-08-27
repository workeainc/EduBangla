<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicClass> */ class AcademicClassFactory extends Factory
{
    protected $model = AcademicClass::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'name' => 'Class '.fake()->unique()->numberBetween(1, 12), 'code' => fake()->unique()->bothify('C##'), 'sort_order' => 1, 'status' => 'active'];
    }
}
