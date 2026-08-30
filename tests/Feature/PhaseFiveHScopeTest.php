<?php

namespace Tests\Feature;

use App\Livewire\Admin\Timetables as AdminTimetables;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveHScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_teacher_student_routes_and_livewire_foreign_ids_are_rejected(): void
    {
        $school = School::factory()->create();
        $foreign = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $foreignAdmin = User::factory()->create();
        SchoolUser::create(['school_id' => $foreign->id, 'user_id' => $foreignAdmin->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id])->get(route('admin.timetables', $foreign))->assertForbidden();
        try {
            $component = app(AdminTimetables::class);
            $component->school = $school;
            $component->publish(999999);
            $this->fail('Forged timetable ID must reject.');
        } catch (ModelNotFoundException) {
        }
        $this->assertDatabaseCount('timetables', 0);
    }

    public function test_guest_inactive_parent_and_unlinked_users_cannot_access_timetable(): void
    {
        $school = School::factory()->create();
        $guest = $this->get(route('student.timetable', $school));
        $guest->assertRedirect(route('login'));
        $parent = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $parent->id, 'role' => 'parent', 'status' => 'active']);
        $this->actingAs($parent)->withSession(['active_school_id' => $school->id])->get(route('student.timetable', $school))->assertForbidden();
        $inactive = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $inactive->id, 'role' => 'teacher', 'status' => 'inactive']);
        $this->actingAs($inactive)->withSession(['active_school_id' => $school->id])->get(route('teacher.timetable', $school))->assertForbidden();
    }
}
