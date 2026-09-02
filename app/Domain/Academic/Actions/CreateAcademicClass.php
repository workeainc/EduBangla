<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicClass;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAcademicClass
{
    public function handle(School $school, array $data): AcademicClass
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique('classes', 'name')->where('school_id', $school->id)],
            'code' => ['required', 'string', 'max:32', Rule::unique('classes', 'code')->where('school_id', $school->id)],
            'sort_order' => ['required', 'integer', 'min:0'],
        ])->validate();

        return DB::transaction(fn () => AcademicClass::create($validated + ['school_id' => $school->id, 'status' => 'active']));
    }
}
