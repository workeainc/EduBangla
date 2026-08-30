<?php

namespace App\Domain\Academic;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TimetableSlot;
use Illuminate\Validation\ValidationException;

class TimetableValidator
{
    public function header(int $schoolId, array $data): array
    {
        $year = AcademicYear::forSchool($schoolId)->whereKey($data['academic_year_id'] ?? null)->firstOrFail();
        $class = AcademicClass::forSchool($schoolId)->whereKey($data['class_id'] ?? null)->firstOrFail();
        $section = Section::forSchool($schoolId)->whereKey($data['section_id'] ?? null)->where('class_id', $class->id)->firstOrFail();

        return compact('year', 'class', 'section');
    }

    public function slot(int $schoolId, array $slot, array $header): array
    {
        $weekday = (int) ($slot['weekday'] ?? -1);
        if ($weekday < 0 || $weekday > 6) {
            throw ValidationException::withMessages(['slots' => 'Weekday must be between 0 and 6.']);
        }
        $starts = (string) ($slot['starts_at'] ?? '');
        $ends = (string) ($slot['ends_at'] ?? '');
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $starts) || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $ends) || strcmp($starts, $ends) >= 0) {
            throw ValidationException::withMessages(['slots' => 'Slot times must be valid and end after start.']);
        }

        $teacherAssignment = TeacherAssignment::query()->where(['school_id' => $schoolId, 'id' => $slot['teacher_assignment_id'] ?? null])->firstOrFail();
        $subjectAssignment = SubjectAssignment::query()->where(['school_id' => $schoolId, 'id' => $slot['subject_assignment_id'] ?? null])->firstOrFail();
        $teacher = Teacher::forSchool($schoolId)->whereKey($teacherAssignment->teacher_id)->where('status', 'active')->firstOrFail();
        if ($teacherAssignment->academic_year_id !== $header['year']->id || $teacherAssignment->class_id !== $header['class']->id || $teacherAssignment->section_id !== $header['section']->id || $teacherAssignment->subject_assignment_id !== $subjectAssignment->id || $subjectAssignment->academic_year_id !== $header['year']->id || $subjectAssignment->class_id !== $header['class']->id || $teacherAssignment->group_scope !== $subjectAssignment->group_scope) {
            throw ValidationException::withMessages(['slots' => 'Teacher and subject assignment do not match timetable scope.']);
        }
        $groupId = $teacherAssignment->group_id ?: null;

        return ['teacher_assignment' => $teacherAssignment, 'subject_assignment' => $subjectAssignment, 'teacher' => $teacher, 'weekday' => $weekday, 'starts_at' => strlen($starts) === 5 ? $starts.':00' : $starts, 'ends_at' => strlen($ends) === 5 ? $ends.':00' : $ends, 'group_id' => $groupId];
    }

    public function assertNoConflicts(array $slots): void
    {
        foreach ($slots as $i => $left) {
            foreach (array_slice($slots, $i + 1) as $right) {
                if ((int) $left['weekday'] !== (int) $right['weekday'] || ! $this->overlap($left['starts_at'], $left['ends_at'], $right['starts_at'], $right['ends_at'])) {
                    continue;
                }
                $sameScope = (int) $left['class_id'] === (int) $right['class_id'] && (int) $left['section_id'] === (int) $right['section_id'] && $this->sameGroupScope($left['group_id'] ?? null, $right['group_id'] ?? null);
                $sameTeacher = (int) $left['teacher_id'] === (int) $right['teacher_id'];
                if ($sameScope || $sameTeacher) {
                    throw ValidationException::withMessages(['slots' => 'Timetable slots overlap for the same class scope or teacher.']);
                }
            }
        }
    }

    public function assertNoPublishedConflicts(int $schoolId, array $slots, int $exceptTimetableId): void
    {
        $existing = TimetableSlot::forSchool($schoolId)
            ->where('timetable_id', '!=', $exceptTimetableId)
            ->whereHas('timetable', fn ($query) => $query->where('status', 'published'))
            ->get(['weekday', 'starts_at', 'ends_at', 'class_id', 'section_id', 'group_id', 'teacher_id']);

        foreach ($slots as $slot) {
            foreach ($existing as $published) {
                if ((int) $slot['weekday'] !== (int) $published->weekday || ! $this->overlap($slot['starts_at'], $slot['ends_at'], $published->starts_at, $published->ends_at)) {
                    continue;
                }
                $sameScope = (int) $slot['class_id'] === (int) $published->class_id && (int) $slot['section_id'] === (int) $published->section_id && $this->sameGroupScope($slot['group_id'] ?? null, $published->group_id);
                if ($sameScope || (int) $slot['teacher_id'] === (int) $published->teacher_id) {
                    throw ValidationException::withMessages(['slots' => 'Timetable conflicts with an already published timetable.']);
                }
            }
        }
    }

    private function sameGroupScope(?int $left, ?int $right): bool
    {
        return $left === null || $right === null || $left === $right;
    }

    private function overlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }
}
