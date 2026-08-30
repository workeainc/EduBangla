<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\TimetableValidator;
use App\Domain\Audit\RecordAudit;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTimetableDraft
{
    public function handle(User $actor, int $schoolId, array $data, ?int $timetableId = null): Timetable
    {
        if (! $this->isAdmin($actor, $schoolId)) {
            throw new AuthorizationException('School admin access is required.');
        }

        return DB::transaction(function () use ($actor, $schoolId, $data, $timetableId) {
            $scope = app(TimetableValidator::class)->header($schoolId, $data);
            $timetable = $timetableId ? Timetable::forSchool($schoolId)->lockForUpdate()->findOrFail($timetableId) : null;
            if ($timetable && $timetable->status !== 'draft') {
                throw ValidationException::withMessages(['timetable' => 'Only draft timetables can be updated.']);
            }
            $payload = ['name' => trim((string) ($data['name'] ?? '')), 'updated_by' => $actor->id];
            if ($payload['name'] === '') {
                throw ValidationException::withMessages(['name' => 'Timetable name is required.']);
            }
            if (! $timetable) {
                $timetable = Timetable::create($payload + ['school_id' => $schoolId, 'academic_year_id' => $scope['year']->id, 'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id, 'created_by' => $actor->id, 'status' => 'draft']);
            } else {
                $timetable->update($payload + ['academic_year_id' => $scope['year']->id, 'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id]);
                $timetable->slots()->delete();
            }
            $validated = [];
            foreach ($data['slots'] ?? [] as $slot) {
                $v = app(TimetableValidator::class)->slot($schoolId, $slot, $scope);
                $validated[] = $v + ['class_id' => $scope['class']->id, 'section_id' => $scope['section']->id, 'teacher_id' => $v['teacher']->id];
            }
            app(TimetableValidator::class)->assertNoConflicts($validated);
            foreach ($validated as $slot) {
                $timetable->slots()->create(['school_id' => $schoolId, 'teacher_assignment_id' => $slot['teacher_assignment']->id, 'subject_assignment_id' => $slot['subject_assignment']->id, 'teacher_id' => $slot['teacher_id'], 'academic_year_id' => $scope['year']->id, 'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id, 'group_id' => $slot['group_id'], 'weekday' => $slot['weekday'], 'starts_at' => $slot['starts_at'], 'ends_at' => $slot['ends_at']]);
            }
            app(RecordAudit::class)->handle($actor, $schoolId, $timetable->wasRecentlyCreated ? 'timetable.draft_created' : 'timetable.draft_updated', $timetable, null, ['status' => 'draft', 'slot_count' => count($validated)]);

            return $timetable->refresh()->load('slots');
        });
    }

    private function isAdmin(User $actor, int $schoolId): bool
    {
        return $actor->schoolMemberships()->where(['school_id' => $schoolId, 'role' => 'school-admin', 'status' => 'active'])->exists();
    }
}
