<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */ class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'class_id' => AcademicClass::factory(), 'name' => 'A', 'code' => 'A', 'capacity' => 40, 'status' => 'active'];
    }
}
