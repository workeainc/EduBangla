<?php

namespace Tests\Feature;

use App\Livewire\Admin\AcademicClassManagement;
use App\Livewire\Admin\AcademicGroupManagement;
use App\Livewire\Admin\AcademicSectionManagement;
use App\Livewire\Admin\AcademicSetupDashboard;
use App\Livewire\Admin\AcademicSubjectManagement;
use App\Livewire\Admin\AcademicYearManagement;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicSetupFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_setup_routes_require_an_active_school_admin(): void
    {
        $school = School::factory()->create();
        $member = User::factory()->create();
        $staff = User::factory()->create();
        $inactive = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $staff->id, 'role' => 'staff', 'status' => 'active']);
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $inactive->id, 'role' => 'school-admin', 'status' => 'inactive']);

        $this->get(route('admin.academic-setup', $school))->assertRedirect(route('login'));
        $this->actingAs($member)->get(route('admin.academic-setup', $school))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.academic-setup', $school))->assertForbidden();
        $this->actingAs($inactive)->get(route('admin.academic-setup', $school))->assertForbidden();

        [$foreignAdmin, $foreignSchool] = $this->admin();
        $this->actingAs($foreignAdmin)->get(route('admin.academic-years', $school))->assertForbidden();
        $this->assertSame(0, AcademicYear::where('school_id', $school->id)->count());
        $this->assertSame(0, AcademicYear::where('school_id', $foreignSchool->id)->count());
    }

    public function test_empty_school_landing_is_scoped_and_guides_the_first_action(): void
    {
        [$admin, $school] = $this->admin();
        $foreign = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $foreign->id]);
        AcademicClass::factory()->create(['school_id' => $foreign->id]);

        Livewire::actingAs($admin)->test(AcademicSetupDashboard::class, ['school' => $school])
            ->assertSee('Create your first academic year')
            ->assertSee('0 configured')
            ->assertSee(route('admin.academic-years', $school), false);
    }

    public function test_admin_can_create_and_activate_academic_years(): void
    {
        [$admin, $school] = $this->admin();
        $first = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026']);

        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])
            ->set('name', '2027')
            ->set('start_date', '2027-01-01')
            ->set('end_date', '2027-12-31')
            ->call('save')
            ->assertHasNoErrors();

        $second = AcademicYear::where(['school_id' => $school->id, 'name' => '2027'])->firstOrFail();
        $this->assertSame('draft', $second->status);

        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])->call('activate', $first->id)->assertHasNoErrors();
        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])->call('activate', $second->id)->assertHasNoErrors();
        $this->assertSame('closed', $first->refresh()->status);
        $this->assertSame('active', $second->refresh()->status);
    }

    public function test_academic_year_validation_rejects_required_invalid_and_duplicate_values(): void
    {
        [$admin, $school] = $this->admin();
        AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026']);

        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])->call('save')
            ->assertHasErrors(['name', 'start_date', 'end_date']);
        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])
            ->set('name', '2027')->set('start_date', '2027-12-31')->set('end_date', '2027-01-01')->call('save')->assertHasErrors('end_date');
        Livewire::actingAs($admin)->test(AcademicYearManagement::class, ['school' => $school])
            ->set('name', '2026')->set('start_date', '2026-01-01')->set('end_date', '2026-12-31')->call('save')->assertHasErrors('name');
    }

    public function test_admin_can_create_classes_sections_subjects_and_groups_with_scoped_validation(): void
    {
        [$admin, $school] = $this->admin();

        Livewire::actingAs($admin)->test(AcademicClassManagement::class, ['school' => $school])
            ->set('name', 'Class 6')->set('code', 'C06')->set('sort_order', 6)->call('save')->assertHasNoErrors();
        $class = AcademicClass::where(['school_id' => $school->id, 'code' => 'C06'])->firstOrFail();
        Livewire::actingAs($admin)->test(AcademicSectionManagement::class, ['school' => $school])
            ->set('class_id', $class->id)->set('name', 'Section A')->set('code', 'A')->set('capacity', 40)->call('save')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(AcademicSubjectManagement::class, ['school' => $school])
            ->set('name', 'Mathematics')->set('code', 'MATH')->set('short_name', 'Math')->call('save')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(AcademicGroupManagement::class, ['school' => $school])
            ->set('name', 'Science')->set('code', 'SCI')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'class_id' => $class->id, 'code' => 'A']);
        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'code' => 'MATH']);
        $this->assertDatabaseHas('groups', ['school_id' => $school->id, 'code' => 'SCI']);
    }

    public function test_catalog_natural_keys_and_section_parent_scope_are_enforced(): void
    {
        [$admin, $school] = $this->admin();
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 6', 'code' => 'C06']);
        $foreign = School::factory()->create();
        $foreignClass = AcademicClass::factory()->create(['school_id' => $foreign->id]);
        Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'A', 'code' => 'A']);

        Livewire::actingAs($admin)->test(AcademicClassManagement::class, ['school' => $school])
            ->set('name', 'Class 6')->set('code', 'NEW')->set('sort_order', 0)->call('save')->assertHasErrors('name');
        Livewire::actingAs($admin)->test(AcademicClassManagement::class, ['school' => $school])
            ->set('name', 'New')->set('code', 'C06')->set('sort_order', -1)->call('save')->assertHasErrors(['code', 'sort_order']);
        Livewire::actingAs($admin)->test(AcademicSectionManagement::class, ['school' => $school])
            ->set('class_id', $foreignClass->id)->set('name', 'Foreign')->set('code', 'F')->set('capacity', 40)->call('save')->assertHasErrors('class_id');
        $this->assertDatabaseMissing('sections', ['school_id' => $school->id, 'code' => 'F']);
        Livewire::actingAs($admin)->test(AcademicSectionManagement::class, ['school' => $school])
            ->set('class_id', $class->id)->set('name', 'B')->set('code', 'B')->set('capacity', 0)->call('save')->assertHasErrors('capacity');
        Livewire::actingAs($admin)->test(AcademicSectionManagement::class, ['school' => $school])
            ->set('class_id', $class->id)->set('name', 'A')->set('code', 'B')->set('capacity', 40)->call('save')->assertHasErrors('name');
        Livewire::actingAs($admin)->test(AcademicSectionManagement::class, ['school' => $school])
            ->set('class_id', $class->id)->set('name', 'B')->set('code', 'A')->set('capacity', 40)->call('save')->assertHasErrors('code');
    }

    public function test_subject_and_group_duplicates_are_scoped_to_the_current_school(): void
    {
        [$admin, $school] = $this->admin();
        $foreign = School::factory()->create();

        Livewire::actingAs($admin)->test(AcademicSubjectManagement::class, ['school' => $school])
            ->set('name', 'English')->set('code', 'ENG')->call('save')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(AcademicSubjectManagement::class, ['school' => $school])
            ->set('name', 'English')->set('code', 'E2')->call('save')->assertHasErrors('name');
        Livewire::actingAs($admin)->test(AcademicGroupManagement::class, ['school' => $school])
            ->set('name', 'Science')->set('code', 'SCI')->call('save')->assertHasNoErrors();
        Livewire::actingAs($admin)->test(AcademicGroupManagement::class, ['school' => $school])
            ->set('name', 'Arts')->set('code', 'SCI')->call('save')->assertHasErrors('code');

        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'code' => 'ENG']);
        $this->assertDatabaseHas('groups', ['school_id' => $school->id, 'code' => 'SCI']);
        $this->assertSame(0, Subject::where('school_id', $foreign->id)->count());
    }

    /** @return array{User, School} */
    private function admin(): array
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);

        return [$admin, $school];
    }
}
