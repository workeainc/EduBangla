<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\ArchiveTimetable;
use App\Domain\Academic\Actions\PublishTimetable;
use App\Domain\Academic\Actions\SaveTimetableDraft;
use App\Domain\Academic\Queries\StudentTimetableQuery;
use App\Domain\Academic\Queries\TeacherTimetableQuery;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveHTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherUser = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $teacherUser->id, 'role' => 'teacher', 'status' => 'active']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $teacherUser->id]);
        $subjectAssignment = SubjectAssignment::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'group_scope' => 0]);
        $teacherAssignment = TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $subjectAssignment->id, 'group_scope' => 0]);

        return compact('school', 'admin', 'year', 'class', 'section', 'subject', 'teacher', 'teacherUser', 'subjectAssignment', 'teacherAssignment');
    }

    private function student(array $f): array
    {
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'role' => 'student', 'status' => 'active']);
        $profile = Student::factory()->create(['school_id' => $f['school']->id, 'user_id' => $user->id, 'status' => 'active']);
        $enrollment = Enrollment::create(['school_id' => $f['school']->id, 'student_id' => $profile->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'roll' => Student::count() + 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);

        return compact('user', 'profile', 'enrollment');
    }

    private function draft(array $f, array $slots = []): Timetable
    {
        return app(SaveTimetableDraft::class)->handle($f['admin'], $f['school']->id, ['name' => 'Weekly routine '.(Timetable::count() + 1), 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'slots' => $slots]);
    }

    private function slot(array $f, int $weekday = 0, string $start = '09:00', string $end = '10:00', ?int $teacherAssignmentId = null, ?int $subjectAssignmentId = null): array
    {
        return ['teacher_assignment_id' => $teacherAssignmentId ?: $f['teacherAssignment']->id, 'subject_assignment_id' => $subjectAssignmentId ?: $f['subjectAssignment']->id, 'weekday' => $weekday, 'starts_at' => $start, 'ends_at' => $end];
    }

    public function test_lifecycle_snapshots_and_archive_preserve_history(): void
    {
        $f = $this->fixture();
        $student = $this->student($f);
        $timetable = $this->draft($f, [$this->slot($f)]);
        $published = app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $timetable->id);
        $this->assertSame('published', $published->status);
        $snapshot = $published->slots->first()->snapshot;
        $f['teacher']->update(['first_name' => 'Changed']);
        $student['enrollment']->update(['status' => 'inactive']);
        $this->assertSame($snapshot, $published->fresh()->slots->first()->snapshot);
        try {
            $published->update(['name' => 'Rewrite']);
            $this->fail('Published timetable mutation must fail.');
        } catch (\RuntimeException) {
        }
        $archived = app(ArchiveTimetable::class)->handle($f['admin'], $f['school']->id, $published->id);
        $this->assertSame('archived', $archived->status);
        $this->assertCount(1, $archived->slots);
        $this->expectException(ValidationException::class);
        app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $archived->id);
    }

    public function test_empty_invalid_and_conflicting_drafts_are_rejected_without_publish(): void
    {
        $f = $this->fixture();
        $empty = $this->draft($f);
        try {
            app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $empty->id);
            $this->fail('Empty timetable must not publish.');
        } catch (ValidationException) {
        }
        $this->assertSame('draft', $empty->fresh()->status);
        try {
            $this->draft($f, [$this->slot($f, 0, '10:00', '09:00')]);
            $this->fail('Invalid time range must not save.');
        } catch (ValidationException) {
        }
    }

    public function test_overlapping_same_scope_or_teacher_and_mismatched_assignments_reject(): void
    {
        $f = $this->fixture();
        $subjectTwo = Subject::factory()->create(['school_id' => $f['school']->id, 'name' => 'Mathematics Two', 'code' => 'MATH-2']);
        $assignmentTwo = SubjectAssignment::create(['school_id' => $f['school']->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'subject_id' => $subjectTwo->id, 'group_scope' => 0]);
        $teacherAssignmentTwo = TeacherAssignment::create(['school_id' => $f['school']->id, 'teacher_id' => $f['teacher']->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'subject_assignment_id' => $assignmentTwo->id, 'group_scope' => 0]);
        $this->expectException(ValidationException::class);
        $this->draft($f, [$this->slot($f), $this->slot($f, 0, '09:30', '10:30', $teacherAssignmentTwo->id, $assignmentTwo->id)]);
    }

    public function test_foreign_scope_and_teacher_assignment_are_rejected(): void
    {
        $f = $this->fixture();
        $foreign = $this->fixture();
        foreach ([
            ['academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'teacher_assignment_id' => $foreign['teacherAssignment']->id, 'subject_assignment_id' => $foreign['subjectAssignment']->id],
        ] as $override) {
            try {
                $this->draft($f, [array_merge($this->slot($f), $override)]);
                $this->fail('Foreign or mismatched timetable source must reject.');
            } catch (\Throwable $e) {
                $this->assertTrue($e instanceof ModelNotFoundException || $e instanceof ValidationException);
            }
        }
        $this->assertDatabaseCount('timetables', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'timetable.draft_created']);
    }

    public function test_publication_rolls_back_after_first_snapshot(): void
    {
        $f = $this->fixture();
        $subjectTwo = Subject::factory()->create(['school_id' => $f['school']->id, 'name' => 'Mathematics Two', 'code' => 'MATH-2']);
        $assignmentTwo = SubjectAssignment::create(['school_id' => $f['school']->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'subject_id' => $subjectTwo->id, 'group_scope' => 0]);
        $teacherTwo = TeacherAssignment::create(['school_id' => $f['school']->id, 'teacher_id' => $f['teacher']->id, 'academic_year_id' => $f['year']->id, 'class_id' => $f['class']->id, 'section_id' => $f['section']->id, 'subject_assignment_id' => $assignmentTwo->id, 'group_scope' => 0]);
        $timetable = $this->draft($f, [$this->slot($f), $this->slot($f, 1, '09:00', '10:00', $teacherTwo->id, $assignmentTwo->id)]);
        try {
            app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $timetable->id, fn () => throw new \RuntimeException('injected timetable failure'));
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('injected timetable failure', $e->getMessage());
        }
        $fresh = $timetable->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertTrue($fresh->slots->every(fn ($slot) => $slot->snapshot === null));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'timetable.published']);
    }

    public function test_publishing_rejects_conflicts_with_existing_published_timetable(): void
    {
        $f = $this->fixture();
        app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $this->draft($f, [$this->slot($f)])->id);
        $draft = $this->draft($f, [$this->slot($f, 0, '09:30', '10:30')]);

        $this->expectException(ValidationException::class);
        app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $draft->id);
    }

    public function test_teacher_and_student_queries_are_ownership_and_enrollment_scoped(): void
    {
        $f = $this->fixture();
        $student = $this->student($f);
        $timetable = app(PublishTimetable::class)->handle($f['admin'], $f['school']->id, $this->draft($f, [$this->slot($f)])->id);
        $this->assertCount(1, app(TeacherTimetableQuery::class)->for($f['school']->id, $f['teacherUser']->id));
        $this->assertCount(1, app(StudentTimetableQuery::class)->for($f['school']->id, $student['user']->id));
        $student['enrollment']->update(['status' => 'inactive']);
        $this->assertCount(0, app(StudentTimetableQuery::class)->for($f['school']->id, $student['user']->id));
        $foreign = $this->fixture();
        $this->expectException(ModelNotFoundException::class);
        app(TeacherTimetableQuery::class)->for($foreign['school']->id, $f['teacherUser']->id);
        $this->assertSame('published', $timetable->fresh()->status);
    }
}
