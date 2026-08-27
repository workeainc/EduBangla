<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSubjectAssignment
{
    public function handle(array $data): SubjectAssignment
    {
        return DB::transaction(function () use ($data) {
            $school = (int) $data['school_id'];
            foreach ([AcademicYear::findOrFail($data['academic_year_id']), AcademicClass::findOrFail($data['class_id']), Subject::findOrFail($data['subject_id'])] as $m) {
                if ($m->school_id !== $school) {
                    throw ValidationException::withMessages(['school_id' => 'Invalid tenant relationship.']);
                }
            }if (! empty($data['group_id'])) {
                $g = AcademicGroup::findOrFail($data['group_id']);
                if ($g->school_id !== $school || ! ClassGroup::where(['school_id' => $school, 'class_id' => $data['class_id'], 'group_id' => $g->id])->exists()) {
                    throw ValidationException::withMessages(['group_id' => 'Group is not applicable to this class.']);
                }
            }$data['group_scope'] = $data['group_id'] ?? 0;

            return SubjectAssignment::create($data);
        });
    }
}
