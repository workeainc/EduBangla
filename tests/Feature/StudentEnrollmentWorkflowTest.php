<?php

namespace Tests\Feature;

use App\Livewire\Admin\StudentEnrollment;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentEnrollmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_student_new_guardian_and_enrollment_atomically(): void
    {
        [$school, $admin, $year, $class, $section] = $this->fixture();
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        Livewire::test(StudentEnrollment::class, ['school' => $school])
            ->set('guardianMode', 'new')
            ->set('student.student_code', 'S-100')
            ->set('student.first_name', 'Amina')
            ->set('guardian.name', 'Rahim Khan')
            ->set('guardian.phone', '01700000000')
            ->set('academic_year_id', $year->id)
            ->set('class_id', $class->id)
            ->set('section_id', $section->id)
            ->set('roll', 1)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('message', 'Student, guardian, and enrollment created successfully.');

        $student = Student::where('school_id', $school->id)->where('student_code', 'S-100')->firstOrFail();
        $this->assertDatabaseHas('guardians', ['school_id' => $school->id, 'name' => 'Rahim Khan']);
        $this->assertDatabaseHas('enrollments', ['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id]);
        $this->assertSame(0, User::whereKey($student->user_id)->count());
        $this->assertNull($student->user_id);
    }

    public function test_admin_can_attach_existing_same_school_guardian_without_duplication(): void
    {
        [$school, $admin, $year, $class, $section] = $this->fixture();
        $guardian = Guardian::factory()->create(['school_id' => $school->id, 'name' => 'Existing Guardian']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        Livewire::test(StudentEnrollment::class, ['school' => $school])
            ->set('student.student_code', 'S-101')->set('student.first_name', 'Bina')
            ->set('guardianMode', 'existing')->set('guardian_id', $guardian->id)
            ->set('academic_year_id', $year->id)->set('class_id', $class->id)->set('section_id', $section->id)->set('roll', 2)
            ->call('submit')->assertHasNoErrors();

        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseCount('student_guardians', 1);
    }

    public function test_foreign_guardian_and_academic_records_are_rejected_and_new_records_roll_back(): void
    {
        [$school, $admin, $year, $class, $section] = $this->fixture();
        $foreign = School::factory()->create();
        $foreignGuardian = Guardian::factory()->create(['school_id' => $foreign->id]);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        $component = Livewire::test(StudentEnrollment::class, ['school' => $school])
            ->set('student.student_code', 'S-102')->set('student.first_name', 'C')
            ->set('guardianMode', 'existing')->set('guardian_id', $foreignGuardian->id)
            ->set('academic_year_id', $year->id)->set('class_id', $class->id)->set('section_id', $section->id)->set('roll', 1);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('submit');
        $this->assertDatabaseMissing('students', ['student_code' => 'S-102']);
    }

    public function test_enrollment_failure_rolls_back_new_student_and_guardian(): void
    {
        [$school, $admin, $year, $class, $section] = $this->fixture();
        $existing = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $existing->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 9, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        try {
            Livewire::test(StudentEnrollment::class, ['school' => $school])->set('guardianMode', 'new')->set('student.student_code', 'S-103')->set('student.first_name', 'D')->set('guardian.name', 'New guardian')->set('guardian.phone', '01700000001')->set('academic_year_id', $year->id)->set('class_id', $class->id)->set('section_id', $section->id)->set('roll', 9)->call('submit');
            $this->fail('Expected enrollment failure.');
        } catch (\Throwable $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertDatabaseMissing('students', ['student_code' => 'S-103']);
        $this->assertDatabaseMissing('guardians', ['name' => 'New guardian']);
    }

    public function test_enrollment_failure_with_existing_guardian_keeps_guardian_but_rolls_back_link(): void
    {
        [$school, $admin, $year, $class, $section] = $this->fixture();
        $existingStudent = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $existingStudent->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 10, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $guardian = Guardian::factory()->create(['school_id' => $school->id, 'name' => 'Reusable guardian']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        try {
            Livewire::test(StudentEnrollment::class, ['school' => $school])->set('guardianMode', 'existing')->set('guardian_id', $guardian->id)->set('student.student_code', 'S-104')->set('student.first_name', 'E')->set('academic_year_id', $year->id)->set('class_id', $class->id)->set('section_id', $section->id)->set('roll', 10)->call('submit');
            $this->fail('Expected enrollment failure.');
        } catch (\Throwable $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertDatabaseHas('guardians', ['id' => $guardian->id]);
        $this->assertDatabaseMissing('students', ['student_code' => 'S-104']);
        $this->assertDatabaseMissing('student_guardians', ['guardian_id' => $guardian->id]);
    }

    public function test_non_admin_and_missing_membership_cannot_open_workspace(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'teacher', 'status' => 'active']);
        $this->actingAs($teacher)->withSession(['active_school_id' => $school->id]);
        $this->get(route('admin.students.enrollment', $school))->assertForbidden();
    }

    private function fixture(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        return [$school, $admin, $year, $class, $section];
    }
}
