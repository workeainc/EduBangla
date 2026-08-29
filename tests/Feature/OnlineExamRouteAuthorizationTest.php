<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineExamRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_student_are_rejected(): void
    {
        $school = School::factory()->create();
        $this->get(route('student.exams', ['school' => $school]))->assertRedirect(route('login'));
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id])->get(route('student.exams', ['school' => $school]))->assertForbidden();
    }

    public function test_student_can_open_own_exam_list(): void
    {
        $school = School::factory()->create();
        $u = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $u->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $u->id]);
        $this->actingAs($u)->withSession(['active_school_id' => $school->id])->get(route('student.exams', ['school' => $school]))->assertOk();
    }
}
