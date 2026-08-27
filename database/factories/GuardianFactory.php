<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Guardian> */ class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'name' => fake()->name(), 'phone' => fake()->phoneNumber(), 'status' => 'active'];
    }
}
