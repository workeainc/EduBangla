<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\TimetableValidator;
use App\Domain\Audit\RecordAudit;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishTimetable
{
    public function handle(User $actor, int $schoolId, int $timetableId, ?\Closure $afterFirstSnapshot = null): Timetable
    {
        if (! $actor->schoolMemberships()->where(['school_id' => $schoolId, 'role' => 'school-admin', 'status' => 'active'])->exists()) {
            throw new AuthorizationException('School admin access is required.');
        }

        return DB::transaction(function () use ($actor, $schoolId, $timetableId, $afterFirstSnapshot) {
            $timetable = Timetable::forSchool($schoolId)->lockForUpdate()->findOrFail($timetableId);
            if ($timetable->status !== 'draft') {
                throw ValidationException::withMessages(['timetable' => 'Only draft timetables can be published.']);
            }
            $scope = app(TimetableValidator::class)->header($schoolId, $timetable->only(['academic_year_id', 'class_id', 'section_id']));
            $slots = $timetable->slots()->where('school_id', $schoolId)->lockForUpdate()->get();
            if ($slots->isEmpty()) {
                throw ValidationException::withMessages(['slots' => 'A timetable must contain at least one slot.']);
            }
            $normalized = [];
            foreach ($slots as $slot) {
                $v = app(TimetableValidator::class)->slot($schoolId, $slot->only(['teacher_assignment_id', 'subject_assignment_id', 'weekday', 'starts_at', 'ends_at']), $scope);
                $normalized[] = $v + ['class_id' => $scope['class']->id, 'section_id' => $scope['section']->id, 'teacher_id' => $v['teacher']->id];
            }
            app(TimetableValidator::class)->assertNoConflicts($normalized);
            app(TimetableValidator::class)->assertNoPublishedConflicts($schoolId, $normalized, $timetable->id);
            foreach ($slots as $offset => $slot) {
                $source = $normalized[$offset];
                $subject = $source['subject_assignment']->subject()->first();
                $slot->update(['snapshot' => ['teacher' => ['id' => $source['teacher']->id, 'employee_code' => $source['teacher']->employee_code, 'name' => trim($source['teacher']->first_name.' '.$source['teacher']->last_name)], 'subject' => ['id' => $subject?->id, 'code' => $subject?->code, 'name' => $subject?->name], 'teacher_assignment_id' => $slot->teacher_assignment_id, 'subject_assignment_id' => $slot->subject_assignment_id, 'academic_year_id' => $slot->academic_year_id, 'class_id' => $slot->class_id, 'section_id' => $slot->section_id, 'group_id' => $slot->group_id, 'weekday' => $slot->weekday, 'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at]]);
                if ($offset === 0 && $afterFirstSnapshot) {
                    $afterFirstSnapshot();
                }
            }
            $publishedAt = now();
            $timetable->update(['status' => 'published', 'published_at' => $publishedAt, 'updated_by' => $actor->id]);
            app(RecordAudit::class)->handle($actor, $schoolId, 'timetable.published', $timetable, ['status' => 'draft'], ['status' => 'published', 'slot_count' => $slots->count()]);

            return $timetable->refresh()->load('slots');
        });
    }
}
