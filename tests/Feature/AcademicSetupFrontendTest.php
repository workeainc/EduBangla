<?php

namespace Tests\Feature;

use App\Livewire\Admin\PhaseThreeManagement;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicSetupFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_readable_scoped_options_and_can_create_class_group(): void
    {
        [$admin, $school] = $this->admin();
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 8']);
        $group = AcademicGroup::factory()->create(['school_id' => $school->id, 'name' => 'Science Group']);
        ClassGroup::create(['school_id' => $school->id, 'class_id' => $class->id, 'group_id' => $group->id]);

        Livewire::actingAs($admin)->withQueryParams([])->test(PhaseThreeManagement::class, ['screen' => 'class-groups'])
            ->assertSee('Class 8')
            ->assertSee('Science Group')
            ->set('form.class_id', $class->id)
            ->set('form.group_id', $group->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_groups', ['school_id' => $school->id, 'class_id' => $class->id, 'group_id' => $group->id]);
    }

    public function test_upstream_changes_clear_incompatible_dependent_selections(): void
    {
        [$admin, $school] = $this->admin();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $otherYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        Livewire::actingAs($admin)->test(PhaseThreeManagement::class, ['screen' => 'subject-assignments'])
            ->set('form.academic_year_id', $year->id)
            ->set('form.class_id', $class->id)
            ->set('form.subject_id', $subject->id)
            ->set('form.group_id', 999)
            ->set('form.academic_year_id', $otherYear->id)
            ->assertSet('form.class_id', null)
            ->assertSet('form.subject_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.subject_assignment_id', null);
    }

    public function test_foreign_class_group_ids_are_rejected_by_existing_action(): void
    {
        [$admin, $school] = $this->admin();
        $foreign = School::factory()->create();
        $class = AcademicClass::factory()->create(['school_id' => $foreign->id]);
        $group = AcademicGroup::factory()->create(['school_id' => $foreign->id]);

        Livewire::actingAs($admin)->test(PhaseThreeManagement::class, ['screen' => 'class-groups'])
            ->set('form.class_id', $class->id)
            ->set('form.group_id', $group->id)
            ->call('save')
            ->assertHasErrors('school_id');

        $this->assertDatabaseMissing('class_groups', ['class_id' => $class->id, 'group_id' => $group->id]);
    }

    /** @return array{User, School} */
    private function admin(): array
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        session(['active_school_id' => $school->id]);

        return [$admin, $school];
    }
}
