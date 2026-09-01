<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\AttendanceSession;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceReportController;

class AttendanceReportFilterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_school_filters_pass_and_foreign_or_stale_filters_are_rejected(): void
    {
        [$school, $admin] = $this->admin();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $foreign = School::factory()->create();
        $foreignYear = AcademicYear::factory()->create(['school_id' => $foreign->id]);
        $foreignClass = AcademicClass::factory()->create(['school_id' => $foreign->id]);
        $foreignSection = Section::factory()->create(['school_id' => $foreign->id, 'class_id' => $foreignClass->id]);

        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);
        $this->get(route('admin.attendance.reports.daily', [$school, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'academic_year_id' => $year->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'class_id' => $class->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'section_id' => $section->id]))->assertOk();
        foreach ([['academic_year_id', $foreignYear->id], ['class_id', $foreignClass->id], ['section_id', $foreignSection->id], ['academic_year_id', 999999]] as [$field, $value]) {
            $this->get(route('admin.attendance.reports.daily', [$school, $field => $value]))->assertSessionHasErrors($field);
        }
    }

    public function test_section_must_match_class_and_month_format_is_validated(): void
    {
        [$school, $admin] = $this->admin();
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $otherClass = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        $this->get(route('admin.attendance.reports.class', [$school, 'class_id' => $otherClass->id, 'section_id' => $section->id]))->assertUnprocessable();
        $this->get(route('admin.attendance.reports.monthly', [$school, 'month' => 'invalid-month']))->assertSessionHasErrors('month');
        $this->get(route('admin.attendance.reports.monthly', [$school, 'month' => '2026-08']))->assertOk();
    }

    public function test_monthly_filters_constrain_aggregates_and_empty_scope_does_not_broaden(): void
    {
        [$school, $admin] = $this->admin();
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026']);
        $otherYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 6']);
        $emptyClass = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 7']);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $otherSection = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Section B', 'code' => 'B']);
        $emptySection = Section::factory()->create(['school_id' => $school->id, 'class_id' => $emptyClass->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherUser = User::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $teacherUser->id]);
        $sa = SubjectAssignment::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'group_scope' => 0]);
        $ta = TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id, 'group_scope' => 0]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $enrollment = \App\Models\Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $session = AttendanceSession::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'teacher_id' => $teacher->id, 'teacher_assignment_id' => $ta->id, 'attendance_date' => '2026-09-01', 'period' => 'Morning', 'status' => 'finalized', 'created_by' => $admin->id]);
        StudentAttendance::create(['school_id' => $school->id, 'attendance_session_id' => $session->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'status' => 'present', 'recorded_at' => now(), 'recorded_by' => $teacherUser->id]);
        $otherSa = SubjectAssignment::create(['school_id' => $school->id, 'academic_year_id' => $otherYear->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'group_scope' => 0]);
        $otherTa = TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $otherYear->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $otherSa->id, 'group_scope' => 0]);
        $otherEnrollment = \App\Models\Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $otherYear->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2027-01-01']);
        $otherSession = AttendanceSession::create(['school_id' => $school->id, 'academic_year_id' => $otherYear->id, 'class_id' => $class->id, 'section_id' => $section->id, 'teacher_id' => $teacher->id, 'teacher_assignment_id' => $otherTa->id, 'attendance_date' => '2026-09-02', 'period' => 'Morning', 'status' => 'finalized', 'created_by' => $admin->id]);
        StudentAttendance::create(['school_id' => $school->id, 'attendance_session_id' => $otherSession->id, 'student_id' => $student->id, 'enrollment_id' => $otherEnrollment->id, 'status' => 'absent', 'recorded_at' => now(), 'recorded_by' => $teacherUser->id]);

        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);
        $matching = app(AttendanceReportController::class)->monthly(Request::create('/reports', 'GET', ['month' => '2026-09', 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id]), $school);
        $matchingRow = collect($matching->getData()['rows'])->first(fn ($row) => $row['s']->id === $student->id);
        $this->assertSame(1, (int) $matchingRow['a']->total);
        $empty = app(AttendanceReportController::class)->monthly(Request::create('/reports', 'GET', ['month' => '2026-09', 'academic_year_id' => $year->id, 'class_id' => $emptyClass->id, 'section_id' => $emptySection->id]), $school);
        $emptyRow = collect($empty->getData()['rows'])->first(fn ($row) => $row['s']->id === $student->id);
        $this->assertSame(0, (int) $emptyRow['a']->total);
        $yearOnly = app(AttendanceReportController::class)->monthly(Request::create('/reports', 'GET', ['month' => '2026-09', 'academic_year_id' => $year->id]), $school);
        $this->assertSame(1, (int) collect($yearOnly->getData()['rows'])->first(fn ($row) => $row['s']->id === $student->id)['a']->total);
        $sectionOnly = app(AttendanceReportController::class)->monthly(Request::create('/reports', 'GET', ['month' => '2026-09', 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $otherSection->id]), $school);
        $this->assertSame(0, (int) collect($sectionOnly->getData()['rows'])->first(fn ($row) => $row['s']->id === $student->id)['a']->total);
    }

    private function admin(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        return [$school, $admin];
    }
}
