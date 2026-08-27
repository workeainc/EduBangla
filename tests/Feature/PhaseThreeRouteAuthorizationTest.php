<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function member(User $u, School $s, string $r): void
    {
        SchoolUser::create(['user_id' => $u->id, 'school_id' => $s->id, 'role' => $r, 'status' => 'active']);
    }

    public function test_admin_routes_are_tenant_and_role_protected(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $admin = User::factory()->create();
        $teacher = User::factory()->create();
        $this->member($admin, $a, 'school-admin');
        $this->member($teacher, $a, 'teacher');
        foreach (['admin.teachers', 'admin.staff', 'admin.class-groups', 'admin.subject-assignments', 'admin.teacher-assignments'] as $n) {
            $this->actingAs($admin)->get(route($n, $a))->assertOk();
            $this->actingAs($teacher)->get(route($n, $a))->assertForbidden();
            $this->actingAs($admin)->get(route($n, $b))->assertForbidden();
        }
    }

    public function test_teacher_only_sees_own_profile_routes(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $this->member($u, $s, 'teacher');
        Teacher::factory()->create(['school_id' => $s->id, 'user_id' => $u->id]);
        $this->actingAs($u)->get(route('teacher.assignments', $s))->assertOk();
        $this->actingAs($u)->get(route('teacher.profile', $s))->assertOk();
    }
}
