<?php

namespace App\Domain\Academic\Queries;

use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\TimetableSlot;

class TeacherTimetableQuery
{
    public function for(int $schoolId, int $userId)
    {
        SchoolUser::where(['school_id' => $schoolId, 'user_id' => $userId, 'role' => 'teacher', 'status' => 'active'])->firstOrFail();
        $teacher = Teacher::forSchool($schoolId)->where(['user_id' => $userId, 'status' => 'active'])->firstOrFail();

        return TimetableSlot::forSchool($schoolId)->where('teacher_id', $teacher->id)->whereHas('timetable', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'published'))->with('timetable')->orderBy('weekday')->orderBy('starts_at')->get();
    }
}
