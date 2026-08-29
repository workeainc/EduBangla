<?php

namespace Tests\Feature;

use App\Domain\Promotion\Actions\ApplyPromotion;
use App\Domain\Promotion\Actions\ApprovePromotion;
use App\Domain\Promotion\Actions\UpdatePromotion;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
}
