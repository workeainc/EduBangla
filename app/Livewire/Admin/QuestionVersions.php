<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Domain\Examination\Actions\DeleteQuestionOption;
use App\Domain\Examination\Actions\ReorderQuestionOption;
use App\Domain\Examination\Actions\UpsertQuestionOption;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuestionVersions extends Component
{
    public School $school;

    public Question $question;

    public array $form = [];

    public string $message = '';

    public array $optionForm = [];

    public function mount(School $school, Question $question)
    {
        $this->school = $school;
        $this->question = $question;
        Gate::authorize('update', $school);
        abort_unless($question->school_id === $school->id, 404);
    }

    public function save()
    {
        $this->validate(['form.prompt' => 'required', 'form.marks' => 'required|integer|min:1']);
        app(CreateQuestionVersion::class)->handle($this->question, $this->form + ['school_id' => $this->school->id, 'created_by' => auth()->id()]);
        $this->form = [];
        $this->message = 'নতুন version তৈরি হয়েছে।';
    }

    public function saveOption(): void
    {
        $this->validate(['optionForm.option_key' => 'required', 'optionForm.option_text' => 'required']);
        $version = $this->question->versions()->latest('version')->firstOrFail();
        app(UpsertQuestionOption::class)->handle($version, $this->optionForm + ['school_id' => $this->school->id]);
        $this->optionForm = [];
        $this->message = 'Option সংরক্ষিত হয়েছে।';
    }

    public function deleteOption(int $id): void
    {
        app(DeleteQuestionOption::class)->handle($id, $this->school->id);
        $this->message = 'Option মুছে ফেলা হয়েছে।';
    }

    public function reorderOption(int $id, int $order): void
    {
        app(ReorderQuestionOption::class)->handle($id, $order, $this->school->id);
        $this->message = 'Option order আপডেট হয়েছে।';
    }

    public function markCorrect(int $id): void
    {
        $o = QuestionOption::where('school_id', $this->school->id)->findOrFail($id);
        $o->questionVersion->options()->update(['is_correct' => false]);
        $o->update(['is_correct' => true]);
        $this->message = 'সঠিক উত্তর নির্ধারিত হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.question-versions', ['versions' => $this->question->versions()->with('options')->latest('version')->get()]);
    }
}
