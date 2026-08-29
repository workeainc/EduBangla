<?php

namespace Tests\Feature;

use App\Domain\Promotion\Actions\ActivatePromotionRule;
use App\Domain\Promotion\Actions\ApplyPromotion;
use App\Domain\Promotion\Actions\ApprovePromotion;
use App\Domain\Promotion\Actions\CreatePromotionRule;
use App\Domain\Promotion\Actions\DeactivatePromotionRule;
use App\Domain\Promotion\Actions\UpdatePromotion;
use App\Domain\Promotion\Actions\UpdatePromotionRule;
use App\Livewire\Admin\PromotionRules;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Promotion;
use App\Models\PromotionRule;
use App\Models\ReportCard;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFiveETest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_lifecycle_and_foreign_scope_are_rejected_without_mutation(): void
    {
        $s = School::factory()->create();
        $other = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $c = AcademicClass::factory()->create(['school_id' => $s->id]);
        $section = Section::factory()->create(['school_id' => $s->id, 'class_id' => $c->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $y->id, 'class_id' => $c->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $p = Promotion::create(['school_id' => $s->id, 'academic_year_id' => $y->id, 'student_id' => $st->id, 'source_enrollment_id' => $en->id, 'source_class_id' => $c->id, 'source_section_id' => $section->id, 'target_academic_year_id' => $y->id, 'target_class_id' => $c->id, 'status' => 'draft']);
        $before = $p->fresh()->toArray();
        $this->actingAs($u);
        try {
            app(ApplyPromotion::class)->handle($p, $s->id);
            $this->fail();
        } catch (ValidationException $e) {
        } $this->assertSame($before, $p->fresh()->toArray());
        $this->expectException(ValidationException::class);
        app(ApprovePromotion::class)->handle($p, $other->id);
    }

    public function test_applied_promotion_cannot_be_updated_and_state_is_unchanged(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $c = AcademicClass::factory()->create(['school_id' => $s->id]);
        $p = new Promotion(['school_id' => $s->id, 'academic_year_id' => $y->id, 'student_id' => $st->id, 'source_enrollment_id' => 1, 'source_class_id' => $c->id, 'target_academic_year_id' => $y->id, 'target_class_id' => $c->id, 'status' => 'applied']);
        $before = $p->getAttributes();
        $this->actingAs($u);
        try {
            app(UpdatePromotion::class)->handle($p, ['target_class_id' => $c->id], $s->id);
            $this->fail('Applied mutation should fail.');
        } catch (ValidationException $e) {
            $this->assertSame($before, $p->getAttributes());
        }
    }

    public function test_foreign_promotion_rule_actions_reject_without_mutation(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $rule = new PromotionRule(['school_id' => $a->id, 'active' => true]);
        foreach ([UpdatePromotionRule::class, ActivatePromotionRule::class, DeactivatePromotionRule::class] as $action) {
            try {
                if ($action === UpdatePromotionRule::class) {
                    app($action)->handle($rule, ['active' => false], $b->id);
                } else {
                    app($action)->handle($rule, $b->id);
                } $this->fail('Foreign rule must be rejected.');
            } catch (\Throwable $e) {
                $this->assertInstanceOf(ValidationException::class, $e);
            }
            $this->assertSame($a->id, $rule->school_id);
            $this->assertTrue($rule->active);
        }
    }

    public function test_promotion_rule_lifecycle_actions_mutate_only_same_tenant_rule(): void
    {
        $s = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $s->id]);
        $source = AcademicClass::factory()->create(['school_id' => $s->id]);
        $target = AcademicClass::factory()->create(['school_id' => $s->id]);
        $rule = PromotionRule::create(['school_id' => $s->id, 'academic_year_id' => $year->id, 'source_class_id' => $source->id, 'target_class_id' => $target->id, 'active' => false]);
        $created = app(CreatePromotionRule::class)->handle(['academic_year_id' => $year->id, 'source_class_id' => $source->id, 'target_class_id' => $target->id], $s->id);
        $this->assertSame($s->id, $created->school_id);
        $this->assertTrue(app(ActivatePromotionRule::class)->handle($rule, $s->id)->active);
        $this->assertFalse(app(DeactivatePromotionRule::class)->handle($rule, $s->id)->active);
        $updated = app(UpdatePromotionRule::class)->handle($rule, ['minimum_gpa' => 3], $s->id);
        $this->assertSame('3.00', (string) $updated->minimum_gpa);
    }

    public function test_apply_promotion_missing_report_rolls_back_without_target_enrollment(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $sourceYear = AcademicYear::factory()->create(['school_id' => $s->id, 'name' => '2026']);
        $targetYear = AcademicYear::factory()->create(['school_id' => $s->id, 'name' => '2027']);
        $sourceClass = AcademicClass::factory()->create(['school_id' => $s->id]);
        $targetClass = AcademicClass::factory()->create(['school_id' => $s->id]);
        $section = Section::factory()->create(['school_id' => $s->id, 'class_id' => $sourceClass->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $sourceYear->id, 'class_id' => $sourceClass->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $p = Promotion::create(['school_id' => $s->id, 'academic_year_id' => $sourceYear->id, 'student_id' => $st->id, 'source_enrollment_id' => $en->id, 'source_class_id' => $sourceClass->id, 'source_section_id' => $section->id, 'target_academic_year_id' => $targetYear->id, 'target_class_id' => $targetClass->id, 'status' => 'approved']);
        $this->actingAs($u);
        try {
            app(ApplyPromotion::class)->handle($p, $s->id);
            $this->fail('Missing report must reject.');
        } catch (ValidationException $e) {
            $this->assertDatabaseMissing('enrollments', ['student_id' => $st->id, 'academic_year_id' => $targetYear->id, 'status' => 'active']);
            $this->assertSame('approved', $p->fresh()->status);
            $this->assertSame($en->id, $p->fresh()->source_enrollment_id);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'promotion.applied', 'auditable_id' => $p->id]);
        }
    }

    public function test_apply_promotion_rolls_back_after_target_enrollment_failure(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $sourceYear = AcademicYear::factory()->create(['school_id' => $s->id, 'name' => '2030']);
        $targetYear = AcademicYear::factory()->create(['school_id' => $s->id, 'name' => '2031']);
        $sourceClass = AcademicClass::factory()->create(['school_id' => $s->id]);
        $targetClass = AcademicClass::factory()->create(['school_id' => $s->id]);
        $section = Section::factory()->create(['school_id' => $s->id, 'class_id' => $sourceClass->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $sourceYear->id, 'class_id' => $sourceClass->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $exam = Exam::factory()->create(['school_id' => $s->id, 'academic_year_id' => $sourceYear->id, 'created_by' => $u->id]);
        $result = Result::create(['school_id' => $s->id, 'exam_id' => $exam->id, 'student_id' => $st->id, 'enrollment_id' => $en->id, 'status' => 'published', 'total_obtained' => 80, 'total_marks' => 100, 'percentage' => 80]);
        ReportCard::create(['school_id' => $s->id, 'result_id' => $result->id, 'student_id' => $st->id, 'enrollment_id' => $en->id, 'exam_id' => $exam->id, 'status' => 'published', 'gpa' => 4, 'overall_status' => 'pass']);
        $p = Promotion::create(['school_id' => $s->id, 'academic_year_id' => $sourceYear->id, 'student_id' => $st->id, 'source_enrollment_id' => $en->id, 'source_class_id' => $sourceClass->id, 'source_section_id' => $section->id, 'target_academic_year_id' => $targetYear->id, 'target_class_id' => $targetClass->id, 'status' => 'approved']);
        $this->actingAs($u);
        try {
            app(ApplyPromotion::class)->handle($p, $s->id, fn () => throw new \RuntimeException('forced failure'));
            $this->fail('Forced failure should rollback.');
        } catch (\RuntimeException $e) {
            $this->assertDatabaseMissing('enrollments', ['student_id' => $st->id, 'academic_year_id' => $targetYear->id, 'status' => 'active']);
            $this->assertSame('approved', $p->fresh()->status);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'promotion.applied', 'auditable_id' => $p->id]);
        }
    }

    public function test_create_promotion_rule_rejects_foreign_scope_without_mutation(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $b->id]);
        $source = AcademicClass::factory()->create(['school_id' => $b->id]);
        $target = AcademicClass::factory()->create(['school_id' => $b->id]);
        try {
            app(CreatePromotionRule::class)->handle(['academic_year_id' => $year->id, 'source_class_id' => $source->id, 'target_class_id' => $target->id], $a->id);
            $this->fail('Foreign rule scope must reject.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('promotion_rules', 0);
        }
    }

    public function test_published_report_snapshot_is_not_changed_by_later_grade_rule_update(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $st = Student::factory()->create(['school_id' => $s->id]);
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $c = AcademicClass::factory()->create(['school_id' => $s->id]);
        $section = Section::factory()->create(['school_id' => $s->id, 'class_id' => $c->id]);
        $en = Enrollment::create(['school_id' => $s->id, 'student_id' => $st->id, 'academic_year_id' => $y->id, 'class_id' => $c->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $exam = Exam::factory()->create(['school_id' => $s->id, 'academic_year_id' => $y->id, 'created_by' => $u->id]);
        $result = Result::create(['school_id' => $s->id, 'exam_id' => $exam->id, 'student_id' => $st->id, 'enrollment_id' => $en->id, 'status' => 'published', 'gpa' => 4, 'overall_status' => 'pass']);
        $rule = PromotionRule::create(['school_id' => $s->id, 'academic_year_id' => $y->id, 'source_class_id' => $c->id, 'target_class_id' => AcademicClass::factory()->create(['school_id' => $s->id])->id, 'active' => true]);
        $card = ReportCard::create(['school_id' => $s->id, 'result_id' => $result->id, 'student_id' => $st->id, 'enrollment_id' => $en->id, 'exam_id' => $exam->id, 'status' => 'published', 'gpa' => 4, 'overall_status' => 'pass', 'snapshot' => ['gpa' => 4, 'overall_status' => 'pass', 'letter_grade' => 'A']]);
        $before = $card->fresh()->toArray();
        $rule->update(['minimum_gpa' => 3]);
        $this->assertSame($before, $card->fresh()->toArray());
    }

    public function test_livewire_rule_toggle_rejects_foreign_rule_id_without_mutation(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $u = User::factory()->create();
        SchoolUser::create(['school_id' => $a->id, 'user_id' => $u->id, 'role' => 'school-admin', 'status' => 'active']);
        $y = AcademicYear::factory()->create(['school_id' => $b->id]);
        $c1 = AcademicClass::factory()->create(['school_id' => $b->id]);
        $c2 = AcademicClass::factory()->create(['school_id' => $b->id]);
        $rule = PromotionRule::create(['school_id' => $b->id, 'academic_year_id' => $y->id, 'source_class_id' => $c1->id, 'target_class_id' => $c2->id, 'active' => true]);
        $this->actingAs($u);
        try {
            Livewire::test(PromotionRules::class, ['school' => $a])->call('toggle', $rule->id);
            $this->fail('Foreign rule mutation should fail.');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('promotion_rules', ['id' => $rule->id, 'active' => 1]);
        }
    }
}
