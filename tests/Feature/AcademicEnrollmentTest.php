<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\ActivateAcademicYear;
use App\Domain\Student\Actions\AttachGuardian;
use App\Domain\Student\Actions\CreateEnrollment;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function academicSetup(School $school): array
    {
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);

        return compact('year', 'class', 'section', 'student');
    }

    private function data(array $x): array
    {
        return ['school_id' => $x['student']->school_id, 'student_id' => $x['student']->id, 'academic_year_id' => $x['year']->id, 'class_id' => $x['class']->id, 'section_id' => $x['section']->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01'];
    }

    public function test_academic_records_are_explicitly_scoped_to_school(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $a->id]);
        AcademicYear::factory()->create(['school_id' => $b->id]);
        $this->assertCount(1, AcademicYear::forSchool($a)->get());
        AcademicClass::factory()->create(['school_id' => $a->id]);
        AcademicClass::factory()->create(['school_id' => $b->id]);
        $this->assertCount(1, AcademicClass::forSchool($a)->get());
        $class = AcademicClass::factory()->create(['school_id' => $a->id, 'code' => 'SECOND']);
        Section::factory()->create(['school_id' => $a->id, 'class_id' => $class->id]);
        $foreignClass = AcademicClass::factory()->create(['school_id' => $b->id, 'code' => 'SECOND']);
        Section::factory()->create(['school_id' => $b->id, 'class_id' => $foreignClass->id]);
        $this->assertCount(1, Section::forSchool($a)->get());
        AcademicGroup::factory()->create(['school_id' => $a->id]);
        AcademicGroup::factory()->create(['school_id' => $b->id]);
        $this->assertCount(1, AcademicGroup::forSchool($a)->get());
        Subject::factory()->create(['school_id' => $a->id]);
        Subject::factory()->create(['school_id' => $b->id]);
        $this->assertCount(1, Subject::forSchool($a)->get());
    }

    public function test_only_one_active_year_is_kept_per_school(): void
    {
        $school = School::factory()->create();
        $first = AcademicYear::factory()->create(['school_id' => $school->id]);
        $second = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        app(ActivateAcademicYear::class)->handle($first);
        app(ActivateAcademicYear::class)->handle($second);
        $this->assertSame('closed', $first->refresh()->status);
        $this->assertSame('active', $second->status);
    }

    public function test_student_code_is_unique_per_school_not_platform(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        Student::factory()->create(['school_id' => $a->id, 'student_code' => 'S-1']);
        Student::factory()->create(['school_id' => $b->id, 'student_code' => 'S-1']);
        $this->expectException(QueryException::class);
        Student::factory()->create(['school_id' => $a->id, 'student_code' => 'S-1']);
    }

    public function test_guardians_can_be_shared_within_school_but_not_across_schools(): void
    {
        $a = School::factory()->create();
        $x = $this->academicSetup($a);
        $second = Student::factory()->create(['school_id' => $a->id]);
        $guardian = Guardian::factory()->create(['school_id' => $a->id]);
        $action = app(AttachGuardian::class);
        $action->handle($x['student'], $guardian, 'mother', true);
        $action->handle($second, $guardian, 'mother');
        $this->assertCount(2, $guardian->students);
        $this->expectException(ValidationException::class);
        $action->handle(Student::factory()->create(['school_id' => School::factory()]), $guardian, 'father');
    }

    public function test_valid_enrollment_keeps_history_and_rejects_cross_school_references(): void
    {
        $school = School::factory()->create();
        $x = $this->academicSetup($school);
        $action = app(CreateEnrollment::class);
        $first = $action->handle($this->data($x));
        $next = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $second = $action->handle(array_replace($this->data($x), ['academic_year_id' => $next->id, 'roll' => 2]));
        $this->assertDatabaseCount('enrollments', 2);
        $this->assertNotSame($first->id, $second->id);
        $foreign = $this->academicSetup(School::factory()->create());
        $this->expectException(ValidationException::class);
        $action->handle(array_replace($this->data($x), ['academic_year_id' => $foreign['year']->id]));
    }

    public function test_roll_is_unique_within_school_year_class_section_and_group_scope(): void
    {
        $school = School::factory()->create();
        $x = $this->academicSetup($school);
        $action = app(CreateEnrollment::class);
        $action->handle($this->data($x));
        $student = Student::factory()->create(['school_id' => $school->id]);
        $this->expectException(QueryException::class);
        $action->handle(array_replace($this->data($x), ['student_id' => $student->id]));
    }

    public function test_student_cannot_have_two_active_enrollments_in_same_year(): void
    {
        $school = School::factory()->create();
        $x = $this->academicSetup($school);
        $action = app(CreateEnrollment::class);
        $action->handle($this->data($x));
        $this->expectException(ValidationException::class);
        $action->handle(array_replace($this->data($x), ['roll' => 2]));
    }
}
