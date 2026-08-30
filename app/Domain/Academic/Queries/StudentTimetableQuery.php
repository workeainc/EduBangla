<?php

namespace App\Domain\Academic\Queries;

use App\Models\Enrollment;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\TimetableSlot;

class StudentTimetableQuery
{
    public function for(int $schoolId, int $userId)
    {
        SchoolUser::where(['school_id' => $schoolId, 'user_id' => $userId, 'role' => 'student', 'status' => 'active'])->firstOrFail();
        $student = Student::forSchool($schoolId)->where(['user_id' => $userId, 'status' => 'active'])->firstOrFail();
        $enrollments = Enrollment::forSchool($schoolId)->where(['student_id' => $student->id, 'status' => 'active'])->get(['academic_year_id', 'class_id', 'section_id', 'group_id']);

        if ($enrollments->isEmpty()) {
            return TimetableSlot::query()->whereRaw('1 = 0')->get();
        }

        return TimetableSlot::forSchool($schoolId)->whereHas('timetable', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'published'))->where(function ($q) use ($enrollments) {
            foreach ($enrollments as $enrollment) {
                $q->orWhere(function ($inner) use ($enrollment) {
                    $inner->where('academic_year_id', $enrollment->academic_year_id)->where('class_id', $enrollment->class_id)->where('section_id', $enrollment->section_id)->where(function ($group) use ($enrollment) {
                        $group->whereNull('group_id')->orWhere('group_id', $enrollment->group_id);
                    });
                });
            }
        })->with('timetable')->orderBy('weekday')->orderBy('starts_at')->get();
    }
}
