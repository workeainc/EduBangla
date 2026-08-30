<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveFScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_open_admin_finance_and_student_can_open_own_finance(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $teacher->id, 'role' => 'teacher', 'status' => 'active']);
        $this->actingAs($teacher)->withSession(['active_school_id' => $school->id])->get(route('admin.finance', $school))->assertForbidden();
        $student = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $student->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $student->id, 'status' => 'active']);
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('student.finance', $school))->assertOk();
    }

    public function test_student_foreign_school_finance_route_is_rejected(): void
    {
        $school = School::factory()->create();
        $foreign = School::factory()->create();
        $student = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $student->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $student->id, 'status' => 'active']);
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('student.finance', $foreign))->assertForbidden();
    }
}
