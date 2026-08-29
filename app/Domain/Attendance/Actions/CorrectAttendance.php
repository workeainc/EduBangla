<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\AttendanceStatus;
use App\Domain\Audit\RecordAudit;
use App\Models\SchoolUser;
use App\Models\StudentAttendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectAttendance
{
    public function handle(StudentAttendance $attendance, string $status, int $actorId): StudentAttendance
    {
        if (! in_array($status, AttendanceStatus::values(), true)) {
            throw ValidationException::withMessages(['status' => 'Invalid attendance status.']);
        }
        $session = $attendance->session;
        if (! $session->isFinalized()) {
            throw ValidationException::withMessages(['session' => 'Corrections are only allowed after finalization.']);
        }
        if (! SchoolUser::where('user_id', auth()->id())->where('school_id', $session->school_id)->where('role', 'school-admin')->where('status', 'active')->exists()) {
            throw new AuthorizationException('Only school admins may correct finalized attendance.');
        }
        if ((int) session('active_school_id') !== (int) $session->school_id) {
            abort(404);
        }

        return DB::transaction(function () use ($attendance, $status, $actorId, $session) {
            $before = ['status' => $attendance->status];
            $attendance->update(['status' => $status, 'recorded_at' => now(), 'recorded_by' => $actorId]);
            app(RecordAudit::class)->handle(auth()->user(), $session->school_id, 'attendance.corrected', $attendance, $before, ['status' => $status]);

            return $attendance->refresh();
        });
    }
}
