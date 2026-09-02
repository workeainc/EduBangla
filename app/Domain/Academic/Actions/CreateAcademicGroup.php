<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicGroup;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAcademicGroup
{
    public function handle(School $school, array $data): AcademicGroup
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')->where('school_id', $school->id)],
            'code' => ['required', 'string', 'max:32', Rule::unique('groups', 'code')->where('school_id', $school->id)],
        ])->validate();

        return DB::transaction(fn () => AcademicGroup::create($validated + ['school_id' => $school->id, 'status' => 'active']));
    }
}
