<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HardeningAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_teacher_role_never_substitutes_for_active_school_teacher_membership(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        Role::findOrCreate('Teacher', 'web');
        $user->assignRole('Teacher');
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'staff', 'status' => 'active']);
        Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);

        foreach (['teacher.assignments', 'teacher.profile', 'teacher.attendance', 'teacher.exams', 'teacher.results', 'teacher.report-cards', 'teacher.promotions', 'teacher.timetable'] as $route) {
            $this->actingAs($user)->get(route($route, $school))->assertForbidden();
        }
    }

    public function test_active_school_teacher_membership_remains_the_route_authority(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)->get(route('teacher.assignments', $school))->assertOk();
    }
}
