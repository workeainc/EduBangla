<?php

namespace Tests\Feature;

use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Domain\Examination\Actions\DeleteQuestionOption;
use App\Domain\Examination\Actions\ReorderQuestionOption;
use App\Domain\Examination\Actions\UpsertQuestionOption;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExaminationSecurityMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_school_question_and_option_mutations_are_rejected(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $u = User::factory()->create();
        $sa = Subject::factory()->create(['school_id' => $a->id]);
        $sb = Subject::factory()->create(['school_id' => $b->id]);
        $bank = QuestionBank::create(['school_id' => $a->id, 'subject_id' => $sa->id, 'name' => 'A']);
        $q = Question::create(['school_id' => $a->id, 'question_bank_id' => $bank->id, 'stable_key' => 'A-1', 'type' => 'mcq']);
        $v = app(CreateQuestionVersion::class)->handle($q, ['school_id' => $a->id, 'prompt' => 'P', 'marks' => 1, 'created_by' => $u->id]);
        $this->expectException(ValidationException::class);
        app(UpsertQuestionOption::class)->handle($v, ['school_id' => $b->id, 'option_key' => 'A', 'option_text' => 'x']);
    }

    public function test_mcq_validation_rejects_invalid_state(): void
    {
        $s = School::factory()->create();
        $sub = Subject::factory()->create(['school_id' => $s->id]);
        $u = User::factory()->create();
        $b = QuestionBank::create(['school_id' => $s->id, 'subject_id' => $sub->id, 'name' => 'B']);
        $q = Question::create(['school_id' => $s->id, 'question_bank_id' => $b->id, 'stable_key' => 'Q', 'type' => 'mcq']);
        $v = app(CreateQuestionVersion::class)->handle($q, ['school_id' => $s->id, 'prompt' => 'P', 'marks' => 1, 'created_by' => $u->id]);
        $this->expectException(ValidationException::class);
        app(UpsertQuestionOption::class)->validateVersion($v);
    }

    public function test_option_crud_reorder_and_delete_are_tenant_scoped(): void
    {
        $s = School::factory()->create();
        $sub = Subject::factory()->create(['school_id' => $s->id]);
        $u = User::factory()->create();
        $b = QuestionBank::create(['school_id' => $s->id, 'subject_id' => $sub->id, 'name' => 'B']);
        $q = Question::create(['school_id' => $s->id, 'question_bank_id' => $b->id, 'stable_key' => 'Q-CRUD', 'type' => 'mcq']);
        $v = app(CreateQuestionVersion::class)->handle($q, ['school_id' => $s->id, 'prompt' => 'P', 'marks' => 1, 'created_by' => $u->id]);
        $o = app(UpsertQuestionOption::class)->handle($v, ['school_id' => $s->id, 'option_key' => 'A', 'option_text' => 'one']);
        $o2 = app(UpsertQuestionOption::class)->handle($v, ['school_id' => $s->id, 'option_key' => 'B', 'option_text' => 'two', 'is_correct' => true]);
        app(UpsertQuestionOption::class)->handle($v, ['school_id' => $s->id, 'option_key' => 'C', 'option_text' => 'three']);
        app(ReorderQuestionOption::class)->handle($o2->id, 0, $s->id);
        $this->assertSame(0, $o2->fresh()->sort_order);
        app(DeleteQuestionOption::class)->handle($o->id, $s->id);
        $this->assertDatabaseMissing('question_options', ['id' => $o->id]);
    }
}
