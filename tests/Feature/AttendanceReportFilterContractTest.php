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

class AttendanceReportFilterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_school_filters_pass_and_foreign_or_stale_filters_are_rejected(): void
    {
        [$school, $admin] = $this->admin();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $foreign = School::factory()->create();
        $foreignYear = AcademicYear::factory()->create(['school_id' => $foreign->id]);
        $foreignClass = AcademicClass::factory()->create(['school_id' => $foreign->id]);
        $foreignSection = Section::factory()->create(['school_id' => $foreign->id, 'class_id' => $foreignClass->id]);

        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);
        $this->get(route('admin.attendance.reports.daily', [$school, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'academic_year_id' => $year->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'class_id' => $class->id]))->assertOk();
        $this->get(route('admin.attendance.reports.class', [$school, 'section_id' => $section->id]))->assertOk();
        foreach ([['academic_year_id', $foreignYear->id], ['class_id', $foreignClass->id], ['section_id', $foreignSection->id], ['academic_year_id', 999999]] as [$field, $value]) {
            $this->get(route('admin.attendance.reports.daily', [$school, $field => $value]))->assertSessionHasErrors($field);
        }
    }

    public function test_section_must_match_class_and_month_format_is_validated(): void
    {
        [$school, $admin] = $this->admin();
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $otherClass = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        $this->get(route('admin.attendance.reports.class', [$school, 'class_id' => $otherClass->id, 'section_id' => $section->id]))->assertUnprocessable();
        $this->get(route('admin.attendance.reports.monthly', [$school, 'month' => 'invalid-month']))->assertSessionHasErrors('month');
        $this->get(route('admin.attendance.reports.monthly', [$school, 'month' => '2026-08']))->assertOk();
    }

    private function admin(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        return [$school, $admin];
    }
}
