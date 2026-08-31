<?php

namespace Tests\Feature;

use App\Livewire\Admin\Timetables;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimetableFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_timetable_workspace_renders_scoped_labels_and_empty_state(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        Livewire::test(Timetables::class, ['school' => $school, 'timetable' => null])
            ->assertSee('Academic year')
            ->assertSee('No slots yet')
            ->assertSee('No timetables');
    }
}
