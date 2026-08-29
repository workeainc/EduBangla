<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeAttendance
{
    public function handle(AttendanceSession $session): AttendanceSession
    {
        if ($session->isFinalized()) {
            throw ValidationException::withMessages(['session' => 'Attendance is already finalized.']);
        }

        return DB::transaction(function () use ($session) {
            $session->update(['status' => 'finalized', 'finalized_at' => now()]);

            if ($actor = auth()->user()) {
                app(RecordAudit::class)->handle($actor, $session->school_id, 'attendance.finalized', $session, null, $session->only(['id', 'status', 'finalized_at']));
            }

            return $session->refresh();
        });
    }
}
