<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CorrectExamMark;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamMarkCorrections extends Component
{
    public School $school;

    public Exam $exam;

    public string $message = '';

    public string $reason = '';

    public function mount(School $school, Exam $exam)
    {
        $this->school = $school;
        $this->exam = $exam;
        Gate::authorize('update', $school);
        abort_unless($exam->school_id === $school->id, 404);
    }

    public function correct(int $id, int $marks, ?string $reason = null)
    {
        $mark = ExamMark::where('school_id', $this->school->id)->whereHas('schedule', fn ($q) => $q->where('exam_id', $this->exam->id))->findOrFail($id);
        app(CorrectExamMark::class)->handle($mark, $marks, auth()->id(), $reason ?? $this->reason);
        $this->reason = '';
        $this->message = 'নম্বর সংশোধন হয়েছে এবং audit হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.exam-mark-corrections', ['marks' => ExamMark::with(['student', 'schedule.subject', 'schedule.academicClass', 'schedule.section'])->where('school_id', $this->school->id)->whereHas('schedule', fn ($q) => $q->where('exam_id', $this->exam->id))->get()]);
    }
}
