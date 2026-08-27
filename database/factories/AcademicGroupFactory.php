<?php

namespace Database\Factories;

use App\Models\AcademicGroup;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicGroup> */ class AcademicGroupFactory extends Factory
{
    protected $model = AcademicGroup::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'name' => 'Science', 'code' => 'SCI', 'status' => 'active'];
    }
}
