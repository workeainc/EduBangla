<?php

namespace Tests\Feature;

use App\Domain\Promotion\Actions\ApplyPromotion;
use App\Domain\Promotion\Actions\ApprovePromotion;
use App\Domain\Promotion\Actions\CreatePromotion;
use App\Domain\Promotion\Actions\CreatePromotionRule;
use App\Domain\Promotion\Actions\EvaluatePromotion;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Promotion;
use App\Models\ReportCard;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromotionApplyRollRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_promotion_applies_with_server_allocated_roll(): void
    {
        [$admin, $school, $source, $target] = $this->fixture();
        $promotion = $this->approvedPromotion($admin, $school, $source, $target);

        $applied = app(ApplyPromotion::class)->handle($promotion, $school->id);

        $targetEnrollment = Enrollment::where(['school_id' => $school->id, 'student_id' => $promotion->student_id, 'academic_year_id' => $target['year']->id])->firstOrFail();
        $this->assertSame('applied', $applied->status);
        $this->assertSame(1, $targetEnrollment->roll);
        $this->assertSame($targetEnrollment->id, $applied->target_enrollment_id);
    }

    public function test_roll_uses_exact_target_scope_and_preserves_existing_rolls(): void
    {
        [$admin, $school, $source, $target] = $this->fixture();
        $otherStudent = Student::factory()->create(['school_id' => $school->id]);
        Enrollment::create(['school_id' => $school->id, 'student_id' => $otherStudent->id, 'academic_year_id' => $target['year']->id, 'class_id' => $target['class']->id, 'section_id' => $target['section']->id, 'roll' => 1, 'status' => 'inactive', 'enrolled_at' => '2027-01-01']);
        Enrollment::create(['school_id' => $school->id, 'student_id' => Student::factory()->create(['school_id' => $school->id])->id, 'academic_year_id' => $target['year']->id, 'class_id' => $target['class']->id, 'section_id' => $target['section']->id, 'roll' => 2, 'status' => 'active', 'enrolled_at' => '2027-01-01']);
        Enrollment::create(['school_id' => $school->id, 'student_id' => Student::factory()->create(['school_id' => $school->id])->id, 'academic_year_id' => $target['year']->id, 'class_id' => $target['class']->id, 'section_id' => $source['section']->id, 'roll' => 99, 'status' => 'active', 'enrolled_at' => '2027-01-01']);
        $promotion = $this->approvedPromotion($admin, $school, $source, $target);

        app(ApplyPromotion::class)->handle($promotion, $school->id);

        $this->assertDatabaseHas('enrollments', ['student_id' => $promotion->student_id, 'academic_year_id' => $target['year']->id, 'section_id' => $target['section']->id, 'roll' => 3]);
        $this->assertDatabaseHas('enrollments', ['student_id' => $otherStudent->id, 'roll' => 1, 'status' => 'inactive']);
        $this->assertDatabaseHas('enrollments', ['academic_year_id' => $target['year']->id, 'section_id' => $source['section']->id, 'roll' => 99]);
    }

    public function test_apply_rolls_back_target_enrollment_and_promotion_on_failure_after_allocation(): void
    {
        [$admin, $school, $source, $target] = $this->fixture();
        $promotion = $this->approvedPromotion($admin, $school, $source, $target);

        try {
            app(ApplyPromotion::class)->handle($promotion, $school->id, fn () => throw new \RuntimeException('forced failure'));
            $this->fail('Expected forced failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced failure', $exception->getMessage());
        }

        $this->assertSame('approved', $promotion->fresh()->status);
        $this->assertDatabaseMissing('enrollments', ['student_id' => $promotion->student_id, 'academic_year_id' => $target['year']->id]);
    }

    public function test_repeated_apply_is_rejected_without_duplicate_target_enrollment(): void
    {
        [$admin, $school, $source, $target] = $this->fixture();
        $promotion = $this->approvedPromotion($admin, $school, $source, $target);
        app(ApplyPromotion::class)->handle($promotion, $school->id);

        try {
            app(ApplyPromotion::class)->handle($promotion->fresh(), $school->id);
            $this->fail('Repeated apply must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('promotion', $exception->errors());
        }
        $this->assertSame(1, Enrollment::where(['school_id' => $school->id, 'student_id' => $promotion->student_id, 'academic_year_id' => $target['year']->id])->count());
    }

    /** @return array{User, School, array{year: AcademicYear, class: AcademicClass, section: Section, enrollment: Enrollment}, array{year: AcademicYear, class: AcademicClass, section: Section}} */
    private function fixture(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        $sourceYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026']);
        $targetYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $sourceClass = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 6', 'code' => 'C06']);
        $targetClass = AcademicClass::factory()->create(['school_id' => $school->id, 'name' => 'Class 7', 'code' => 'C07']);
        $sourceSection = Section::factory()->create(['school_id' => $school->id, 'class_id' => $sourceClass->id, 'name' => 'A', 'code' => 'A']);
        $targetSection = Section::factory()->create(['school_id' => $school->id, 'class_id' => $targetClass->id, 'name' => 'A', 'code' => 'A']);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $sourceEnrollment = Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $sourceYear->id, 'class_id' => $sourceClass->id, 'section_id' => $sourceSection->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);

        return [$admin, $school, ['year' => $sourceYear, 'class' => $sourceClass, 'section' => $sourceSection, 'enrollment' => $sourceEnrollment], ['year' => $targetYear, 'class' => $targetClass, 'section' => $targetSection]];
    }

    private function approvedPromotion(User $admin, School $school, array $source, array $target): Promotion
    {
        $exam = Exam::factory()->create(['school_id' => $school->id, 'academic_year_id' => $source['year']->id, 'created_by' => $admin->id]);
        $result = Result::create(['school_id' => $school->id, 'exam_id' => $exam->id, 'student_id' => $source['enrollment']->student_id, 'enrollment_id' => $source['enrollment']->id, 'status' => 'published', 'total_obtained' => 80, 'total_marks' => 100, 'percentage' => 80, 'gpa' => 4, 'overall_status' => 'pass']);
        $card = ReportCard::create(['school_id' => $school->id, 'result_id' => $result->id, 'student_id' => $result->student_id, 'enrollment_id' => $result->enrollment_id, 'exam_id' => $result->exam_id, 'status' => 'published', 'gpa' => 4, 'overall_status' => 'pass']);
        app(CreatePromotionRule::class)->handle(['name' => 'Pass', 'academic_year_id' => $source['year']->id, 'source_class_id' => $source['class']->id, 'target_class_id' => $target['class']->id], $school->id);
        $promotion = app(CreatePromotion::class)->handle(['student_id' => $source['enrollment']->student_id, 'source_enrollment_id' => $source['enrollment']->id, 'academic_year_id' => $source['year']->id, 'source_class_id' => $source['class']->id, 'source_section_id' => $source['section']->id, 'target_academic_year_id' => $target['year']->id, 'target_class_id' => $target['class']->id, 'target_section_id' => $target['section']->id], $school->id);
        $promotion = app(EvaluatePromotion::class)->handle($promotion, $school->id);

        return app(ApprovePromotion::class)->handle($promotion, $school->id);
    }
}
