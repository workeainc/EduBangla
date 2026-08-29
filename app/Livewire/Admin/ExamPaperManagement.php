<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\AddPaperQuestion;
use App\Domain\Examination\Actions\RemovePaperQuestion;
use App\Domain\Examination\Actions\ReorderPaperQuestion;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamSchedule;
use App\Models\QuestionVersion;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamPaperManagement extends Component
{
    public School $school;

    public Exam $exam;

    public ?ExamSchedule $schedule = null;

    public array $form = [];

    public string $message = '';

    public function mount(School $school, Exam $exam)
    {
        $this->school = $school;
        $this->exam = $exam;
        Gate::authorize('update', $school);
        abort_unless($exam->school_id === $school->id, 404);
        $this->schedule = ExamSchedule::where(['school_id' => $school->id, 'exam_id' => $exam->id])->first();
    }

    public function add()
    {
        if (! $this->schedule) {
            return;
        } $p = ExamPaper::firstOrCreate(['school_id' => $this->school->id, 'exam_schedule_id' => $this->schedule->id], ['version' => 1, 'total_marks' => 0]);
        $this->validate(['form.question_version_id' => 'required', 'form.ordinal' => 'required|integer|min:1', 'form.marks' => 'required|integer|min:1']);
        app(AddPaperQuestion::class)->handle($this->form + ['school_id' => $this->school->id, 'exam_paper_id' => $p->id]);
        $p->update(['total_marks' => $p->questions()->sum('marks')]);
        $this->message = 'Question paper-এ যোগ হয়েছে।';
    }

    public function remove(int $id): void
    {
        app(RemovePaperQuestion::class)->handle($id, $this->school->id);
        $this->message = 'Question removed.';
    }

    public function reorder(int $id, int $ordinal): void
    {
        app(ReorderPaperQuestion::class)->handle($id, $ordinal, $this->school->id);
        $this->message = 'Order updated.';
    }

    public function render()
    {
        $paper = $this->schedule ? ExamPaper::with('questions.version.question')->where(['school_id' => $this->school->id, 'exam_schedule_id' => $this->schedule->id])->first() : null;

        return view('livewire.admin.exam-paper', ['paper' => $paper, 'versions' => QuestionVersion::with('question')->where('school_id', $this->school->id)->get()]);
    }
}
