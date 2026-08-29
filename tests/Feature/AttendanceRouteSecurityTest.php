<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function member(User $u, School $s, string $role): void
    {
        SchoolUser::create(['user_id' => $u->id, 'school_id' => $s->id, 'role' => $role, 'status' => 'active']);
    }

    public function test_cross_school_attendance_urls_are_rejected(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $admin = User::factory()->create();
        $this->member($admin, $a, 'school-admin');
        $student = Student::factory()->create(['school_id' => $b->id]);
        $this->actingAs($admin)->get(route('admin.attendance', $b))->assertForbidden();
        foreach (['admin.attendance.reports.daily', 'admin.attendance.reports.monthly', 'admin.attendance.reports.class'] as $name) {
            $this->actingAs($admin)->get(route($name, $b))->assertForbidden();
        }
        $this->actingAs($admin)->get(route('admin.students.attendance', [$b, $student]))->assertForbidden();
    }

    public function test_unauthenticated_and_non_admin_are_blocked(): void
    {
        $s = School::factory()->create();
        $this->get(route('admin.attendance', $s))->assertRedirect();
        $student = User::factory()->create();
        $this->member($student, $s, 'student');
        $this->actingAs($student)->get(route('admin.attendance', $s))->assertForbidden();
    }

    public function test_correction_page_is_admin_only(): void
    {
        $s = School::factory()->create();
        $admin = User::factory()->create();
        $teacher = User::factory()->create();
        $this->member($admin, $s, 'school-admin');
        $this->member($teacher, $s, 'teacher');
        $this->actingAs($admin)->get(route('admin.attendance.corrections', $s))->assertOk();
        $this->actingAs($teacher)->get(route('admin.attendance.corrections', $s))->assertForbidden();
    }
}
