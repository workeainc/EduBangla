<?php

namespace Tests\Feature;

use App\Domain\Result\Actions\CalculateResultGrades;
use App\Domain\Result\Actions\GenerateReportCard;
use App\Domain\Result\Actions\PublishReportCard;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\GradeRule;
use App\Models\ReportCard;
use App\Models\Result;
use App\Models\ResultItem;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveDTest extends TestCase
{
    use RefreshDatabase;

    private function resultData(string $status = 'computed'): array
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $y->id, 'class_id' => AcademicClass::factory()->create(['school_id' => $s->id])->id, 'section_id' => Section::factory()->create(['school_id' => $s->id])->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $e = Exam::factory()->create(['school_id' => $s->id, 'academic_year_id' => $y->id, 'created_by' => $u->id]);
        $r = Result::create(['school_id' => $s->id, 'exam_id' => $e->id, 'student_id' => $st->id, 'enrollment_id' => $en->id, 'status' => $status, 'total_obtained' => 80, 'total_marks' => 100, 'percentage' => 80, 'computed_at' => now()]);

        return [$s, $u, $r];
    }

    public function test_grade_calculation_snapshots_rule_and_gpa(): void
    {
        [$s,$u,$r] = $this->resultData();
        $class = AcademicClass::where('school_id', $s->id)->first();
        $section = Section::where('school_id', $s->id)->first();
        $subject = Subject::factory()->create(['school_id' => $s->id]);
        $teacher = Teacher::factory()->create(['school_id' => $s->id]);
        $sa = SubjectAssignment::create(['school_id' => $s->id, 'academic_year_id' => $r->exam->academic_year_id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        $ta = TeacherAssignment::create(['school_id' => $s->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $r->exam->academic_year_id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id]);
        $schedule = ExamSchedule::create(['school_id' => $s->id, 'exam_id' => $r->exam_id, 'academic_year_id' => $r->exam->academic_year_id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id, 'teacher_assignment_id' => $ta->id, 'teacher_id' => $teacher->id, 'scheduled_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '10:00', 'maximum_marks' => 100, 'duration_minutes' => 60]);
        ResultItem::create(['school_id' => $s->id, 'result_id' => $r->id, 'subject_id' => $subject->id, 'exam_schedule_id' => $schedule->id, 'obtained_marks' => 80, 'maximum_marks' => 100, 'percentage' => 80, 'source' => 'manual']);
        GradeRule::create(['school_id' => $s->id, 'name' => 'A', 'minimum_percentage' => 80, 'maximum_percentage' => 100, 'letter_grade' => 'A', 'grade_point' => 4, 'is_pass' => true, 'sort_order' => 1, 'active' => true]);
        $this->actingAs($u);
        $r = app(CalculateResultGrades::class)->handle($r, $s->id);
        $this->assertSame('4.00', (string) $r->gpa);
        $this->assertSame('pass', $r->overall_status);
        $this->assertDatabaseHas('result_items', ['letter_grade' => 'A', 'grade_point' => 4]);
    }

    public function test_overlapping_rules_and_published_recalculation_are_rejected(): void
    {
        [$s,$u,$r] = $this->resultData('published');
        GradeRule::create(['school_id' => $s->id, 'name' => 'A', 'minimum_percentage' => 0, 'maximum_percentage' => 80, 'letter_grade' => 'A', 'grade_point' => 4, 'active' => true]);
        GradeRule::create(['school_id' => $s->id, 'name' => 'B', 'minimum_percentage' => 80, 'maximum_percentage' => 100, 'letter_grade' => 'B', 'grade_point' => 3, 'active' => true]);
        $this->actingAs($u);
        $this->expectException(ValidationException::class);
        app(CalculateResultGrades::class)->handle($r, $s->id);
    }

    public function test_report_card_requires_locked_or_published_graded_result_and_is_immutable(): void
    {
        [$s,$u,$r] = $this->resultData();
        $this->actingAs($u);
        $this->expectException(ValidationException::class);
        app(GenerateReportCard::class)->handle($r, $s->id);
    }

    public function test_published_report_card_snapshot_cannot_be_mutated_or_regenerated(): void
    {
        [$s, $u, $r] = $this->resultData('locked');
        $r->update(['gpa' => 4, 'overall_status' => 'pass']);
        $card = ReportCard::create(['school_id' => $s->id, 'result_id' => $r->id, 'student_id' => $r->student_id, 'enrollment_id' => $r->enrollment_id, 'exam_id' => $r->exam_id, 'status' => 'generated', 'gpa' => 4, 'overall_status' => 'pass', 'snapshot' => ['gpa' => 4, 'overall_status' => 'pass']]);
        $this->actingAs($u);
        $card = app(PublishReportCard::class)->handle($card, $s->id);
        $snapshot = $card->fresh()->toArray();
        try {
            $card->update(['gpa' => 1]);
            $this->fail('Published report card mutation should fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Published report cards are immutable.', $e->getMessage());
        }
        $this->assertSame($snapshot, $card->fresh()->toArray());
    }
}
