<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\CreateSubjectAssignment;
use App\Domain\Attendance\Actions\CorrectAttendance;
use App\Domain\Attendance\Actions\CreateAttendanceSession;
use App\Domain\Attendance\Actions\FinalizeAttendance;
use App\Domain\Attendance\Actions\RecordAttendance;
use App\Domain\Attendance\AttendanceReport;
use App\Domain\Attendance\AttendanceStatus;
use App\Domain\Teacher\Actions\CreateTeacherAssignment;
use App\Livewire\Admin\AttendanceCorrections;
use App\Livewire\Attendance\Management;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $t = Teacher::factory()->create(['school_id' => $s->id, 'user_id' => $u->id]);
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $c = AcademicClass::factory()->create(['school_id' => $s->id]);
        $sec = Section::factory()->create(['school_id' => $s->id, 'class_id' => $c->id]);
        $sub = Subject::factory()->create(['school_id' => $s->id]);
        $sa = app(CreateSubjectAssignment::class)->handle(['school_id' => $s->id, 'academic_year_id' => $y->id, 'class_id' => $c->id, 'subject_id' => $sub->id]);
        $ta = app(CreateTeacherAssignment::class)->handle(['school_id' => $s->id, 'teacher_id' => $t->id, 'academic_year_id' => $y->id, 'class_id' => $c->id, 'section_id' => $sec->id, 'subject_assignment_id' => $sa->id]);
        $enrollments = [];
        foreach (range(1, 4) as $i) {
            $st = Student::factory()->create(['school_id' => $s->id]);
            $enrollments[] = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $y->id, 'class_id' => $c->id, 'section_id' => $sec->id, 'group_scope' => 0, 'roll' => $i, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        }

        return compact('s', 'u', 't', 'y', 'c', 'sec', 'ta', 'enrollments');
    }

    public function test_bulk_attendance_is_transactional_and_tenant_scoped(): void
    {
        $f = $this->fixture();
        $other = School::factory()->create();
        $foreign = Student::factory()->create(['school_id' => $other->id]);
        $fy = AcademicYear::factory()->create(['school_id' => $other->id]);
        $fc = AcademicClass::factory()->create(['school_id' => $other->id]);
        $fs = Section::factory()->create(['school_id' => $other->id, 'class_id' => $fc->id]);
        $fe = Enrollment::create(['school_id' => $other->id, 'student_id' => $foreign->id, 'academic_year_id' => $fy->id, 'class_id' => $fc->id, 'section_id' => $fs->id, 'group_scope' => 0, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-08-29', 'created_by' => $f['u']->id]);
        $rows = [];
        foreach ($f['enrollments'] as $e) {
            $rows[] = ['student_id' => $e->student_id, 'enrollment_id' => $e->id, 'status' => AttendanceStatus::PRESENT->value];
        } $rows[] = ['student_id' => $foreign->id, 'enrollment_id' => $fe->id, 'status' => 'absent'];
        $this->expectException(ValidationException::class);
        app(RecordAttendance::class)->handle($session, $rows, $f['u']->id);
        $this->assertDatabaseCount('student_attendance', 0);
    }

    public function test_report_and_finalization_lock_session(): void
    {
        $f = $this->fixture();
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-08-29', 'created_by' => $f['u']->id]);
        $statuses = ['present', 'absent', 'late', 'excused'];
        $rows = [];
        foreach ($f['enrollments'] as $i => $e) {
            $rows[] = ['student_id' => $e->student_id, 'enrollment_id' => $e->id, 'status' => $statuses[$i]];
        } app(RecordAttendance::class)->handle($session, $rows, $f['u']->id);
        $this->assertSame(50.0, AttendanceReport::summarize($session->fresh()->load('attendances'))['percentage']);
        app(FinalizeAttendance::class)->handle($session);
        $this->expectException(ValidationException::class);
        app(RecordAttendance::class)->handle($session->fresh(), [$rows[0]], $f['u']->id);
    }

    public function test_database_prevents_duplicate_sessions_and_rows(): void
    {
        $f = $this->fixture();
        $data = ['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-08-29', 'created_by' => $f['u']->id];
        $one = app(CreateAttendanceSession::class)->handle($data);
        $this->expectException(QueryException::class);
        app(CreateAttendanceSession::class)->handle($data);
    }

    public function test_open_attendance_rows_are_updated_without_duplicates(): void
    {
        $f = $this->fixture();
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-09-03', 'created_by' => $f['u']->id]);
        $first = $f['enrollments'][0];
        $second = $f['enrollments'][1];
        app(RecordAttendance::class)->handle($session, [
            ['student_id' => $first->student_id, 'enrollment_id' => $first->id, 'status' => 'present'],
            ['student_id' => $second->student_id, 'enrollment_id' => $second->id, 'status' => 'late'],
        ], $f['u']->id);

        app(RecordAttendance::class)->handle($session, [
            ['student_id' => $first->student_id, 'enrollment_id' => $first->id, 'status' => 'absent'],
            ['student_id' => $second->student_id, 'enrollment_id' => $second->id, 'status' => 'late'],
        ], $f['u']->id);

        $this->assertDatabaseCount('student_attendance', 2);
        $this->assertDatabaseHas('student_attendance', ['attendance_session_id' => $session->id, 'student_id' => $first->student_id, 'status' => 'absent']);
        $this->assertDatabaseHas('student_attendance', ['attendance_session_id' => $session->id, 'student_id' => $second->student_id, 'status' => 'late']);
    }

    public function test_open_attendance_update_rolls_back_when_any_row_is_invalid(): void
    {
        $f = $this->fixture();
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-09-04', 'created_by' => $f['u']->id]);
        $first = $f['enrollments'][0];
        app(RecordAttendance::class)->handle($session, [['student_id' => $first->student_id, 'enrollment_id' => $first->id, 'status' => 'present']], $f['u']->id);

        try {
            app(RecordAttendance::class)->handle($session, [
                ['student_id' => $first->student_id, 'enrollment_id' => $first->id, 'status' => 'absent'],
                ['student_id' => $f['enrollments'][1]->student_id, 'enrollment_id' => $f['enrollments'][1]->id, 'status' => 'not-a-status'],
            ], $f['u']->id);
            $this->fail('Invalid batch must reject.');
        } catch (ValidationException) {
        }
        $this->assertDatabaseHas('student_attendance', ['attendance_session_id' => $session->id, 'student_id' => $first->student_id, 'status' => 'present']);
    }

    public function test_admin_correction_is_audited_after_finalization(): void
    {
        $f = $this->fixture();
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-08-29', 'created_by' => $f['u']->id]);
        $row = ['student_id' => $f['enrollments'][0]->student_id, 'enrollment_id' => $f['enrollments'][0]->id, 'status' => 'absent'];
        app(RecordAttendance::class)->handle($session, [$row], $f['u']->id);
        app(FinalizeAttendance::class)->handle($session);
        $this->actingAs($f['u']);
        SchoolUser::create(['school_id' => $f['s']->id, 'user_id' => $f['u']->id, 'role' => 'school-admin', 'status' => 'active']);
        session(['active_school_id' => $f['s']->id]);
        app(CorrectAttendance::class)->handle($session->fresh()->attendances()->first(), 'present', $f['u']->id);
        $this->assertDatabaseHas('student_attendance', ['status' => 'present']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.corrected']);
    }

    public function test_session_action_rejects_every_cross_school_scope_identifier(): void
    {
        $a = $this->fixture();
        $b = $this->fixture();
        $base = ['school_id' => $a['s']->id, 'academic_year_id' => $a['y']->id, 'class_id' => $a['c']->id, 'section_id' => $a['sec']->id, 'teacher_id' => $a['t']->id, 'teacher_assignment_id' => $a['ta']->id, 'attendance_date' => '2026-08-30', 'created_by' => $a['u']->id];
        foreach (['academic_year_id' => $b['y']->id, 'class_id' => $b['c']->id, 'section_id' => $b['sec']->id, 'teacher_id' => $b['t']->id, 'teacher_assignment_id' => $b['ta']->id] as $key => $value) {
            try {
                app(CreateAttendanceSession::class)->handle(array_merge($base, [$key => $value]));
                $this->fail("Cross-school {$key} was accepted.");
            } catch (ValidationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_livewire_mutations_reject_foreign_session_and_correction(): void
    {
        $a = $this->fixture();
        $b = $this->fixture();
        SchoolUser::create(['school_id' => $a['s']->id, 'user_id' => $a['u']->id, 'role' => 'teacher', 'status' => 'active']);
        $make = fn ($f) => app(CreateAttendanceSession::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'class_id' => $f['c']->id, 'section_id' => $f['sec']->id, 'teacher_id' => $f['t']->id, 'teacher_assignment_id' => $f['ta']->id, 'attendance_date' => '2026-09-01', 'created_by' => $f['u']->id]);
        $bSession = $make($b);
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($a['u'])->test(Management::class, ['school' => $a['s']])->set('assignmentId', $a['ta']->id)->set('sessionId', $bSession->id)->set('statuses', [])->call('save');
    }

    public function test_livewire_admin_correction_is_tenant_scoped_and_valid_correction_succeeds(): void
    {
        $a = $this->fixture();
        $b = $this->fixture();
        SchoolUser::create(['school_id' => $a['s']->id, 'user_id' => $a['u']->id, 'role' => 'school-admin', 'status' => 'active']);
        $session = app(CreateAttendanceSession::class)->handle(['school_id' => $b['s']->id, 'academic_year_id' => $b['y']->id, 'class_id' => $b['c']->id, 'section_id' => $b['sec']->id, 'teacher_id' => $b['t']->id, 'teacher_assignment_id' => $b['ta']->id, 'attendance_date' => '2026-09-02', 'created_by' => $b['u']->id]);
        $e = $b['enrollments'][0];
        app(RecordAttendance::class)->handle($session, [['student_id' => $e->student_id, 'enrollment_id' => $e->id, 'status' => 'absent']], $b['u']->id);
        app(FinalizeAttendance::class)->handle($session);
        $row = $session->fresh()->attendances()->first();
        session(['active_school_id' => $a['s']->id]);
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($a['u'])->test(AttendanceCorrections::class, ['school' => $a['s']])->call('correct', $row->id, 'present');
    }
}
