<?php

namespace App\Livewire\Student;

use App\Domain\Examination\Actions\SaveExamAnswer;
use App\Domain\Examination\Actions\SubmitExamAttempt;
use App\Models\ExamAttempt;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Attempt extends Component
{
    public School $school;

    public ExamAttempt $attempt;

    public array $answers = [];

    public string $message = '';

    public function mount(School $school, ExamAttempt $attempt)
    {
        $this->school = $school;
        $this->attempt = $attempt->load('questions.answer');
        Gate::authorize('view', $school);
        abort_unless($attempt->school_id === $school->id && $attempt->student->user_id === auth()->id(), 404);
    }

    public function saveAnswer(int $questionId, $answer)
    {
        app(SaveExamAnswer::class)->handle($this->attempt, $questionId, $answer, $this->school->id);
        $this->message = 'উত্তর সংরক্ষিত হয়েছে।';
    }

    public function submit()
    {
        app(SubmitExamAttempt::class)->handle($this->attempt, $this->school->id);
        $this->message = 'পরীক্ষা জমা হয়েছে।';
    }

    public function render()
    {
        return view('livewire.student.attempt');
    }
}
