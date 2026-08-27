<?php

namespace App\Domain\Academic\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\ClassGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateClassGroup
{
    public function handle(int $schoolId, int $classId, int $groupId): ClassGroup
    {
        return DB::transaction(function () use ($schoolId, $classId, $groupId) {
            $class = AcademicClass::findOrFail($classId);
            $group = AcademicGroup::findOrFail($groupId);
            if ($class->school_id !== $schoolId || $group->school_id !== $schoolId) {
                throw ValidationException::withMessages(['school_id' => 'Invalid tenant relationship.']);
            }

            return ClassGroup::firstOrCreate(['school_id' => $schoolId, 'class_id' => $classId, 'group_id' => $groupId]);
        });
    }
}
