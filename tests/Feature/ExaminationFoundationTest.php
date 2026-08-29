<?php

namespace Tests\Feature;

use App\Domain\Examination\Actions\CreateExam;
use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Domain\Examination\Actions\TransitionExam;
use App\Models\AcademicYear;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExaminationFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function base(): array
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $y = AcademicYear::factory()->create(['school_id' => $s->id]);
        $sub = Subject::factory()->create(['school_id' => $s->id]);
        $type = ExamType::create(['school_id' => $s->id, 'name' => 'Class Test', 'code' => 'CT', 'active' => true]);

        return compact('s', 'u', 'y', 'sub', 'type');
    }

    public function test_exam_lifecycle_is_explicit_and_tenant_scoped(): void
    {
        $f = $this->base();
        $e = app(CreateExam::class)->handle(['school_id' => $f['s']->id, 'academic_year_id' => $f['y']->id, 'exam_type_id' => $f['type']->id, 'name' => 'Midterm', 'code' => 'MID', 'created_by' => $f['u']->id]);
        $this->assertSame('draft', $e->status);
        app(TransitionExam::class)->handle($e, 'scheduled');
        $this->assertSame('scheduled', $e->fresh()->status);
        $this->expectException(ValidationException::class);
        app(TransitionExam::class)->handle($e->fresh(), 'published');
    }

    public function test_question_versions_increment_without_mutating_history(): void
    {
        $f = $this->base();
        $bank = QuestionBank::create(['school_id' => $f['s']->id, 'subject_id' => $f['sub']->id, 'name' => 'Math bank']);
        $q = Question::create(['school_id' => $f['s']->id, 'question_bank_id' => $bank->id, 'stable_key' => 'Q-1', 'type' => 'mcq']);
        $v1 = app(CreateQuestionVersion::class)->handle($q, ['school_id' => $f['s']->id, 'prompt' => 'এক', 'marks' => 1, 'created_by' => $f['u']->id]);
        $v2 = app(CreateQuestionVersion::class)->handle($q, ['school_id' => $f['s']->id, 'prompt' => 'দুই', 'marks' => 2, 'created_by' => $f['u']->id]);
        $this->assertSame(1, $v1->version);
        $this->assertSame(2, $v2->version);
        $this->assertSame('এক', $v1->fresh()->prompt);
    }
}
