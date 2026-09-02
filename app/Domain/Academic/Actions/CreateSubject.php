<?php

namespace App\Domain\Academic\Actions;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateSubject
{
    public function handle(School $school, array $data): Subject
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->where('school_id', $school->id)],
            'code' => ['required', 'string', 'max:32', Rule::unique('subjects', 'code')->where('school_id', $school->id)],
            'short_name' => ['nullable', 'string', 'max:64'],
        ])->validate();

        return DB::transaction(fn () => Subject::create($validated + ['school_id' => $school->id, 'status' => 'active']));
    }
}
