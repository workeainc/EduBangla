<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAcademicYear
{
    public function handle(School $school, array $data): AcademicYear
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'name')->where('school_id', $school->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ])->validate();

        return DB::transaction(fn () => AcademicYear::create($validated + ['school_id' => $school->id, 'status' => 'draft']));
    }
}
