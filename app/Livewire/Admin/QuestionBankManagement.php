<?php

namespace App\Livewire\Admin;

use App\Domain\Audit\RecordAudit;
use App\Domain\Examination\Actions\CreateQuestion;
use App\Domain\Examination\Actions\CreateQuestionBank;
use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Domain\Examination\Actions\UpsertQuestionOption;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionVersion;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuestionBankManagement extends Component
{
    public School $school;

    public ?QuestionBank $bank = null;

    public ?Question $question = null;

    public string $mode = 'banks';

    public array $form = [];

    public string $message = '';

    public array $optionForm = [];

    public function mount(School $school, string $mode = 'banks', ?QuestionBank $bank = null, ?Question $question = null)
    {
        $this->school = $school;
        $this->mode = $mode;
        Gate::authorize('update', $school);
        if ($bank) {
            abort_unless($bank->school_id === $school->id, 404);
            $this->bank = $bank;
            $this->form = $bank->only(['name', 'subject_id', 'language', 'curriculum_version']);
        }
        if ($question) {
            abort_unless($question->school_id === $school->id, 404);
            $this->question = $question;
            $this->form = $question->only(['question_bank_id', 'stable_key', 'type', 'topic', 'learning_objective', 'difficulty']);
        }
    }

    public function saveBank()
    {
        $this->validate(['form.name' => 'required', 'form.subject_id' => 'required']);
        if ($this->bank) {
            $this->bank->update($this->form);
            $this->message = 'Question bank আপডেট হয়েছে।';
            $this->form = [];

            return;
        }
        app(CreateQuestionBank::class)->handle($this->form + ['school_id' => $this->school->id, 'status' => 'active']);
        $this->form = [];
        $this->message = 'Question bank তৈরি হয়েছে।';
    }

    public function toggleBank(int $id): void
    {
        $bank = QuestionBank::where('school_id', $this->school->id)->findOrFail($id);
        $bank->update(['status' => $bank->status === 'active' ? 'inactive' : 'active']);
        if (auth()->user()) {
            app(RecordAudit::class)->handle(auth()->user(), $this->school->id, 'question_bank.status_changed', $bank);
        }
        $this->message = 'Question bank status আপডেট হয়েছে।';
    }

    public function saveQuestion()
    {
        $this->validate(['form.question_bank_id' => 'required', 'form.stable_key' => 'required', 'form.type' => 'required']);
        if ($this->question) {
            $this->question->update($this->form);
            $this->message = 'Question আপডেট হয়েছে।';
            $this->form = [];

            return;
        }
        app(CreateQuestion::class)->handle($this->form + ['school_id' => $this->school->id, 'status' => 'active']);
        $this->form = [];
        $this->message = 'Question তৈরি হয়েছে।';
    }

    public function toggleQuestion(int $id): void
    {
        $question = Question::where('school_id', $this->school->id)->findOrFail($id);
        $question->update(['status' => $question->status === 'active' ? 'inactive' : 'active']);
        if (auth()->user()) {
            app(RecordAudit::class)->handle(auth()->user(), $this->school->id, 'question.status_changed', $question);
        }
        $this->message = 'Question status আপডেট হয়েছে।';
    }

    public function newVersion(int $id)
    {
        $q = Question::where('school_id', $this->school->id)->findOrFail($id);
        app(CreateQuestionVersion::class)->handle($q, ['school_id' => $this->school->id, 'prompt' => $this->form['prompt'] ?? '', 'marks' => (int) ($this->form['marks'] ?? 1), 'created_by' => auth()->id()]);
        $this->message = 'নতুন version তৈরি হয়েছে।';
    }

    public function saveOption(int $versionId): void
    {
        $this->validate(['optionForm.option_key' => 'required|string|max:10', 'optionForm.option_text' => 'required|string', 'optionForm.is_correct' => 'boolean']);
        $version = QuestionVersion::where('school_id', $this->school->id)->findOrFail($versionId);
        app(UpsertQuestionOption::class)->handle($version, $this->optionForm + ['school_id' => $this->school->id]);
        $this->optionForm = [];
        $this->message = 'Option সংরক্ষিত হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.question-bank-management', ['banks' => QuestionBank::with('questions')->where('school_id', $this->school->id)->get(), 'questions' => Question::with(['bank', 'versions'])->where('school_id', $this->school->id)->get(), 'subjects' => Subject::where('school_id', $this->school->id)->get()]);
    }
}
