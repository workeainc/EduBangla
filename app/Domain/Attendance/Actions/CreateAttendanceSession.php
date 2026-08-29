<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAttendanceSession
{
    public function handle(array $data): AttendanceSession
    {
        return DB::transaction(function () use ($data) {
            $school = (int) $data['school_id'];
            $assignment = TeacherAssignment::with('teacher')->findOrFail($data['teacher_assignment_id']);
            $year = AcademicYear::findOrFail($data['academic_year_id']);
            $class = AcademicClass::findOrFail($data['class_id']);
            $section = Section::findOrFail($data['section_id']);
            $teacher = Teacher::findOrFail($data['teacher_id']);
            foreach ([$assignment, $year, $class, $section, $teacher] as $m) {
                if ((int) $m->school_id !== $school) {
                    throw ValidationException::withMessages(['school_id' => 'Invalid tenant relationship.']);
                }
            }
            if ($assignment->teacher_id !== $teacher->id || $assignment->academic_year_id !== $year->id || $assignment->class_id !== $class->id || $assignment->section_id !== $section->id) {
                throw ValidationException::withMessages(['teacher_assignment_id' => 'Assignment does not match session scope.']);
            }
            if ($section->class_id !== $class->id) {
                throw ValidationException::withMessages(['section_id' => 'Section is not in selected class.']);
            }

            $session = AttendanceSession::create(array_merge($data, ['status' => 'draft']));
            if ($actor = User::find($data['created_by'])) {
                app(RecordAudit::class)->handle($actor, $school, 'attendance.created', $session, null, $session->only(['id', 'attendance_date', 'period', 'status']));
            }

            return $session;
        });
    }
}
