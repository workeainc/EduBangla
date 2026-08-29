<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateQuestion;
use App\Domain\Examination\Actions\CreateQuestionBank;
use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuestionBankManagement extends Component
{
    public School $school;

    public string $mode = 'banks';

    public array $form = [];

    public string $message = '';

    public function mount(School $school, string $mode = 'banks')
    {
        $this->school = $school;
        $this->mode = $mode;
        Gate::authorize('update', $school);
    }

    public function saveBank()
    {
        $this->validate(['form.name' => 'required', 'form.subject_id' => 'required']);
        app(CreateQuestionBank::class)->handle($this->form + ['school_id' => $this->school->id, 'status' => 'active']);
        $this->form = [];
        $this->message = 'Question bank তৈরি হয়েছে।';
    }

    public function saveQuestion()
    {
        $this->validate(['form.question_bank_id' => 'required', 'form.stable_key' => 'required', 'form.type' => 'required']);
        app(CreateQuestion::class)->handle($this->form + ['school_id' => $this->school->id, 'status' => 'active']);
        $this->form = [];
        $this->message = 'Question তৈরি হয়েছে।';
    }

    public function newVersion(int $id)
    {
        $q = Question::where('school_id', $this->school->id)->findOrFail($id);
        app(CreateQuestionVersion::class)->handle($q, ['school_id' => $this->school->id, 'prompt' => $this->form['prompt'] ?? '', 'marks' => (int) ($this->form['marks'] ?? 1), 'created_by' => auth()->id()]);
        $this->message = 'নতুন version তৈরি হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.question-bank-management', ['banks' => QuestionBank::with('questions')->where('school_id', $this->school->id)->get(), 'questions' => Question::with(['bank', 'versions'])->where('school_id', $this->school->id)->get(), 'subjects' => Subject::where('school_id', $this->school->id)->get()]);
    }
}
