<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamManagement;
use App\Models\AcademicYear;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExaminationFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_exam_workspace_uses_readable_labels_and_server_lifecycle_control(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['name' => 'Pilot School']);
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $type = ExamType::create(['school_id' => $school->id, 'name' => 'Midterm', 'code' => 'MID', 'active' => true]);

        Livewire::actingAs($admin)->test(ExamManagement::class, ['school' => $school])
            ->assertSee('Academic year')
            ->assertSee('Exam type')
            ->set('form.name', 'First term')
            ->set('form.code', 'TERM-1')
            ->set('form.academic_year_id', $year->id)
            ->set('form.exam_type_id', $type->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('First term')
            ->assertSee('Draft');
    }
}
