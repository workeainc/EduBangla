<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateQuestionVersion;
use App\Models\Question;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuestionVersions extends Component
{
    public School $school;

    public Question $question;

    public array $form = [];

    public string $message = '';

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

    public function render()
    {
        return view('livewire.admin.question-versions', ['versions' => $this->question->versions()->latest('version')->get()]);
    }
}
