<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportingFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_renders_scoped_human_readable_filters_and_empty_state(): void
    {
        [$school, $admin] = $this->admin();
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 8']);
        Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Section A']);
        $foreign = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $foreign->id, 'name' => 'Foreign Year']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        $response = $this->get(route('admin.attendance.reports.class', [$school, 'academic_year_id' => $year->id, 'class_id' => $class->id]));
        $response->assertOk()->assertSee(['2026 Academic Year', 'Class 8', 'Section A', 'No students found', 'Active filters']);
        $response->assertDontSee('Foreign Year');
        $response->assertSee('x-model="year"', false)->assertSee('x-model="classId"', false);
    }

    private function admin(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        return [$school, $admin];
    }
}
