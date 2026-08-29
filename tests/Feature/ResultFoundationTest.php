<?php

namespace Tests\Feature;

use App\Domain\Result\Actions\LockResult;
use App\Domain\Result\Actions\PublishResult;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResultFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_lifecycle_requires_compute_then_lock_then_publish(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $student = Student::factory()->create(['school_id' => $s->id]);
        $year = AcademicYear::factory()->create(['school_id' => $s->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => AcademicClass::factory()->create(['school_id' => $s->id])->id, 'section_id' => Section::factory()->create(['school_id' => $s->id])->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $exam = Exam::factory()->create(['school_id' => $s->id, 'academic_year_id' => $year->id, 'created_by' => $u->id]);
        $r = Result::create(['school_id' => $s->id, 'exam_id' => $exam->id, 'student_id' => $student->id, 'enrollment_id' => $en->id, 'status' => 'computed', 'total_obtained' => 80, 'total_marks' => 100, 'percentage' => 80, 'computed_at' => now()]);
        $this->actingAs($u);
        $this->expectException(ValidationException::class);
        app(PublishResult::class)->handle($r, $s->id);
        $r = app(LockResult::class)->handle($r, $s->id);
        $this->assertSame('locked', $r->status);
        $r = app(PublishResult::class)->handle($r, $s->id);
        $this->assertSame('published', $r->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'result.locked', 'auditable_id' => $r->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'result.published', 'auditable_id' => $r->id]);
    }
}
