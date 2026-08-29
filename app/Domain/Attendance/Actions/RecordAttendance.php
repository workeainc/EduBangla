<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\AttendanceStatus;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\StudentAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendance
{
    public function handle(AttendanceSession $session, array $rows, int $actorId): void
    {
        DB::transaction(function () use ($session, $rows, $actorId) {
            if ($session->isFinalized()) {
                throw ValidationException::withMessages(['session' => 'Finalized attendance is read-only.']);
            }
            $validated = [];
            foreach ($rows as $row) {
                if (! in_array($row['status'] ?? '', AttendanceStatus::values(), true)) {
                    throw ValidationException::withMessages(['status' => 'Invalid attendance status.']);
                }
                $enrollment = Enrollment::findOrFail($row['enrollment_id']);
                if ((int) $enrollment->school_id !== $session->school_id || (int) $enrollment->academic_year_id !== $session->academic_year_id || (int) $enrollment->class_id !== $session->class_id || (int) $enrollment->section_id !== $session->section_id || (int) $enrollment->student_id !== (int) $row['student_id']) {
                    throw ValidationException::withMessages(['attendance' => 'Student enrollment is outside session scope.']);
                }
                $validated[] = ['school_id' => $session->school_id, 'attendance_session_id' => $session->id, 'student_id' => $row['student_id'], 'enrollment_id' => $enrollment->id, 'status' => $row['status'], 'recorded_at' => now(), 'recorded_by' => $actorId, 'remarks' => $row['remarks'] ?? null, 'created_at' => now(), 'updated_at' => now()];
            }
            if ($validated) {
                StudentAttendance::insert($validated);
            }
        });
    }
}
