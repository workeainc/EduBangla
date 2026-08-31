<?php

namespace Tests\Feature;

use App\Livewire\Teacher\MyAssignments;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherWorkspaceFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_assignments_are_scoped_and_human_readable(): void
    {
        [$user, $school, $teacher] = $this->teacher();
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 8']);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Section A']);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $subjectAssignment = SubjectAssignment::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'group_scope' => 0]);
        TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $subjectAssignment->id, 'group_scope' => 0]);

        $other = Teacher::factory()->create(['school_id' => $school->id]);
        TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $other->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $subjectAssignment->id, 'group_scope' => 0]);

        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
        Livewire::actingAs($user)->test(MyAssignments::class)
            ->assertSee(['2026 Academic Year', 'Class 8', 'Section A', 'Mathematics'])
            ->assertDontSee($other->employee_code);
    }

    public function test_teacher_workspace_routes_reject_inactive_and_foreign_memberships(): void
    {
        [$user, $school] = $this->teacher();
        $foreign = School::factory()->create();

        $this->actingAs($user)->withSession(['active_school_id' => $school->id])
            ->get(route('teacher.assignments', $foreign))->assertForbidden();

        SchoolUser::where(['school_id' => $school->id, 'user_id' => $user->id])->update(['status' => 'inactive']);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id])
            ->get(route('teacher.assignments', $school))->assertForbidden();
    }

    /** @return array{User, School, Teacher} */
    private function teacher(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);

        return [$user, $school, $teacher];
    }
}
