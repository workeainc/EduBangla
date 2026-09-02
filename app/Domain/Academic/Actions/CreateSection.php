<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicClass;
use App\Models\School;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSection
{
    public function handle(School $school, array $data): Section
    {
        $validated = Validator::make($data, [
            'class_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ])->validate();

        return DB::transaction(function () use ($school, $validated) {
            $class = AcademicClass::forSchool($school)->lockForUpdate()->find($validated['class_id']);
            if (! $class) {
                throw ValidationException::withMessages(['class_id' => 'Choose a class from this school.']);
            }

            Validator::make($validated, [
                'name' => [Rule::unique('sections', 'name')->where('school_id', $school->id)->where('class_id', $class->id)],
                'code' => [Rule::unique('sections', 'code')->where('school_id', $school->id)->where('class_id', $class->id)],
            ])->validate();

            return Section::create($validated + ['school_id' => $school->id, 'class_id' => $class->id, 'status' => 'active']);
        });
    }
}
