<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveEScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_student_promotion_routes_enforce_role_and_tenant(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $guest = $this->get(route('teacher.promotions', ['school' => $school]));
        $guest->assertRedirect(route('login'));
        $student = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $student->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $student->id, 'status' => 'active']);
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('admin.promotions', ['school' => $school]))->assertForbidden();
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('student.promotions', ['school' => $other]))->assertForbidden();
    }

    public function test_student_can_open_own_promotion_screen(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id])->get(route('student.promotions', ['school' => $school]))->assertOk();
    }
}
