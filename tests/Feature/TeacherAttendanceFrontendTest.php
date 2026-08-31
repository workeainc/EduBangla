<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\CreateSubjectAssignment;
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
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherAttendanceFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_load_readable_scope_save_reload_and_finalize_attendance(): void
    {
        [$user, $school, $assignment, $enrollments] = $this->fixture();
        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);

        $component = Livewire::actingAs($user)->test(Management::class, ['school' => $school])
            ->assertSee(['Class 8', 'Section A', 'Mathematics'])
            ->set('assignmentId', $assignment->id)
            ->call('loadStudents')
            ->assertSee(['Alice Student', 'Bob Student', 'New attendance session'])
            ->set('statuses.'.$enrollments[0]->student_id, 'absent')
            ->call('save')
            ->assertSee('উপস্থিতি সংরক্ষণ হয়েছে।');

        $component->call('loadStudents')
            ->assertSet('statuses.'.$enrollments[0]->student_id, 'absent')
            ->call('finalize')
            ->assertSee('read-only');

        $this->assertDatabaseCount('student_attendance', 2);
        $this->assertDatabaseHas('attendance_sessions', ['id' => $component->get('sessionId'), 'status' => 'finalized']);
    }

    public function test_teacher_cannot_load_foreign_assignment_or_finalize_foreign_session(): void
    {
        [$user, $school] = $this->teacherOnly();
        $foreign = $this->fixture();
        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($user)->test(Management::class, ['school' => $school])
            ->set('assignmentId', $foreign[2]->id)
            ->call('loadStudents');
    }

    /** @return array{User, School, TeacherAssignment, array<int, Enrollment>} */
    private function fixture(): array
    {
        [$user, $school, $teacher] = $this->teacherOnly();
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 8']);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Section A']);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $subjectAssignment = app(CreateSubjectAssignment::class)->handle(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        $assignment = TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $subjectAssignment->id, 'group_scope' => 0]);
        $enrollments = [];
        foreach ([['Alice', 'Student'], ['Bob', 'Student']] as [$first, $last]) {
            $student = Student::factory()->create(['school_id' => $school->id, 'first_name' => $first, 'last_name' => $last]);
            $enrollments[] = Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'group_scope' => 0, 'roll' => count($enrollments) + 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        }

        return [$user, $school, $assignment, $enrollments];
    }

    /** @return array{User, School, Teacher} */
    private function teacherOnly(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);

        return [$user, $school, $teacher];
    }
}
