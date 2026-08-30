<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveTimetable
{
    public function handle(User $actor, int $schoolId, int $timetableId): Timetable
    {
        if (! $actor->schoolMemberships()->where(['school_id' => $schoolId, 'role' => 'school-admin', 'status' => 'active'])->exists()) {
            throw new AuthorizationException('School admin access is required.');
        }

        return DB::transaction(function () use ($actor, $schoolId, $timetableId) {
            $timetable = Timetable::forSchool($schoolId)->lockForUpdate()->findOrFail($timetableId);
            if ($timetable->status !== 'published') {
                throw ValidationException::withMessages(['timetable' => 'Only published timetables can be archived.']);
            }
            $timetable->update(['status' => 'archived', 'archived_at' => now(), 'updated_by' => $actor->id]);
            app(RecordAudit::class)->handle($actor, $schoolId, 'timetable.archived', $timetable, ['status' => 'published'], ['status' => 'archived']);

            return $timetable->refresh()->load('slots');
        });
    }
}
