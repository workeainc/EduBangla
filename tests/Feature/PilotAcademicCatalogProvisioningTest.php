<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\ProvisionPilotAcademicCatalog;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PilotAcademicCatalogProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisions_only_the_explicit_school_and_uses_existing_year_activation(): void
    {
        $school = School::factory()->create(['name' => 'Pilot School']);
        $unrelatedSchool = School::factory()->create();

        $summary = app(ProvisionPilotAcademicCatalog::class)->handle($school, $this->catalog());

        $this->assertSame($school->id, $summary['school']['id']);
        $this->assertSame(1, $summary['academic_year']['created']);
        $this->assertTrue($summary['academic_year']['activated']);
        $this->assertDatabaseHas('academic_years', ['school_id' => $school->id, 'name' => '2026', 'status' => 'active']);
        $this->assertDatabaseHas('classes', ['school_id' => $school->id, 'name' => 'Class 6', 'code' => 'C06', 'sort_order' => 6]);
        $class = AcademicClass::where(['school_id' => $school->id, 'code' => 'C06'])->firstOrFail();
        $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'class_id' => $class->id, 'code' => 'A', 'capacity' => 40]);
        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'code' => 'MATH', 'short_name' => 'Math']);
        $this->assertSame(0, AcademicYear::where('school_id', $unrelatedSchool->id)->count());
        $this->assertSame(0, AcademicClass::where('school_id', $unrelatedSchool->id)->count());
        $this->assertSame(0, Section::where('school_id', $unrelatedSchool->id)->count());
        $this->assertSame(0, Subject::where('school_id', $unrelatedSchool->id)->count());
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Guardian::count());
        $this->assertSame(0, Enrollment::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, TeacherAssignment::count());
    }

    public function test_exact_rerun_resolves_records_without_duplicates_and_optional_groups_are_supported(): void
    {
        $school = School::factory()->create();
        $catalog = $this->catalog(['groups' => [['name' => 'Science', 'code' => 'SCI', 'status' => 'active']]]);
        app(ProvisionPilotAcademicCatalog::class)->handle($school, $catalog);

        $summary = app(ProvisionPilotAcademicCatalog::class)->handle($school, $catalog);

        $this->assertSame(0, $summary['academic_year']['created']);
        $this->assertSame(1, $summary['academic_year']['resolved']);
        $this->assertSame(1, $summary['classes']['resolved']);
        $this->assertSame(2, $summary['sections']['resolved']);
        $this->assertSame(2, $summary['subjects']['resolved']);
        $this->assertSame(1, $summary['groups']['resolved']);
        $this->assertSame(1, AcademicYear::where('school_id', $school->id)->count());
        $this->assertSame(1, AcademicClass::where('school_id', $school->id)->count());
        $this->assertSame(2, Section::where('school_id', $school->id)->count());
        $this->assertSame(2, Subject::where('school_id', $school->id)->count());
        $this->assertSame(1, AcademicGroup::where('school_id', $school->id)->count());
    }

    public function test_incompatible_existing_catalog_fails_closed_and_rolls_back_new_records(): void
    {
        $school = School::factory()->create();
        $existing = Subject::factory()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'short_name' => 'Algebra',
            'status' => 'active',
        ]);

        try {
            app(ProvisionPilotAcademicCatalog::class)->handle($school, $this->catalog());
            $this->fail('Expected an incompatible existing subject conflict.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('catalog', $exception->errors());
        }

        $this->assertSame(0, AcademicYear::where('school_id', $school->id)->count());
        $this->assertSame(0, AcademicClass::where('school_id', $school->id)->count());
        $this->assertSame(0, Section::where('school_id', $school->id)->count());
        $this->assertSame(1, Subject::where('school_id', $school->id)->count());
        $this->assertSame('Algebra', $existing->fresh()->short_name);
    }

    public function test_command_requires_one_existing_school_and_never_provisions_all_schools(): void
    {
        $target = School::factory()->create(['name' => 'Target School']);
        $other = School::factory()->create(['name' => 'Other School']);
        config(['edubangla-pilot-catalog.catalog' => $this->catalog()]);

        $this->assertSame(1, Artisan::call('edubangla:provision-pilot-catalog', ['--school-id' => 999999, '--force' => true]));
        $this->assertSame(0, AcademicYear::count());

        $this->assertSame(0, Artisan::call('edubangla:provision-pilot-catalog', ['--school-id' => $target->id, '--force' => true]));
        $this->assertStringContainsString("[{$target->id}] Target School", Artisan::output());
        $this->assertSame(1, AcademicYear::where('school_id', $target->id)->count());
        $this->assertSame(0, AcademicYear::where('school_id', $other->id)->count());
    }

    /** @return array<string, mixed> */
    private function catalog(array $overrides = []): array
    {
        return array_replace_recursive([
            'academic_year' => [
                'name' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'activate' => true,
            ],
            'classes' => [
                ['name' => 'Class 6', 'code' => 'C06', 'sort_order' => 6, 'status' => 'active'],
            ],
            'sections' => [
                ['class_code' => 'C06', 'name' => 'Section A', 'code' => 'A', 'capacity' => 40, 'status' => 'active'],
                ['class_code' => 'C06', 'name' => 'Section B', 'code' => 'B', 'capacity' => 40, 'status' => 'active'],
            ],
            'subjects' => [
                ['name' => 'Mathematics', 'code' => 'MATH', 'short_name' => 'Math', 'status' => 'active'],
                ['name' => 'English', 'code' => 'ENG', 'short_name' => 'English', 'status' => 'active'],
            ],
            'groups' => [],
        ], $overrides);
    }
}
