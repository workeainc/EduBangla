<?php

namespace Tests\Feature;

use App\Domain\Academic\Actions\CreateSubjectAssignment;
use App\Domain\Teacher\Actions\CreateTeacherAssignment;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function base(School $s): array
    {
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $c = AcademicClass::factory()->create(['school_id' => $s->id]);
        $sec = Section::factory()->create(['school_id' => $s->id, 'class_id' => $c->id]);
        $sub = Subject::factory()->create(['school_id' => $s->id]);
        $t = Teacher::factory()->create(['school_id' => $s->id]);

        return compact('y', 'c', 'sec', 'sub', 't');
    }

    public function test_assignments_validate_tenant_and_keep_history(): void
    {
        $s = School::factory()->create();
        $x = $this->base($s);
        $sa = app(CreateSubjectAssignment::class)->handle(['school_id' => $s->id, 'academic_year_id' => $x['y']->id, 'class_id' => $x['c']->id, 'subject_id' => $x['sub']->id]);
        $a = app(CreateTeacherAssignment::class)->handle(['school_id' => $s->id, 'teacher_id' => $x['t']->id, 'academic_year_id' => $x['y']->id, 'class_id' => $x['c']->id, 'section_id' => $x['sec']->id, 'subject_assignment_id' => $sa->id]);
        $next = AcademicYear::factory()->create(['school_id' => $s->id, 'name' => '2027']);
        $sa2 = app(CreateSubjectAssignment::class)->handle(['school_id' => $s->id, 'academic_year_id' => $next->id, 'class_id' => $x['c']->id, 'subject_id' => $x['sub']->id]);
        $b = app(CreateTeacherAssignment::class)->handle(['school_id' => $s->id, 'teacher_id' => $x['t']->id, 'academic_year_id' => $next->id, 'class_id' => $x['c']->id, 'section_id' => $x['sec']->id, 'subject_assignment_id' => $sa2->id]);
        $this->assertNotSame($a->id, $b->id);
        $foreign = $this->base(School::factory()->create());
        $this->expectException(ValidationException::class);
        app(CreateTeacherAssignment::class)->handle(['school_id' => $s->id, 'teacher_id' => $x['t']->id, 'academic_year_id' => $foreign['y']->id, 'class_id' => $x['c']->id, 'section_id' => $x['sec']->id, 'subject_assignment_id' => $sa->id]);
    }
}
