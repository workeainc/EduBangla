<?php

namespace App\Livewire\Admin;

use App\Domain\Result\Actions\ComputeExamResult;
use App\Domain\Result\Actions\LockResult;
use App\Domain\Result\Actions\PublishResult;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ResultManagement extends Component
{
    public School $school;

    public string $message = '';

    public ?int $filterExamId = null;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function compute(int $examId): void
    {
        $exam = Exam::where('school_id', $this->school->id)->findOrFail($examId);
        app(ComputeExamResult::class)->handle($exam, $this->school->id);
        $this->message = 'Result compute হয়েছে।';
    }

    public function lock(int $id): void
    {
        $r = Result::where('school_id', $this->school->id)->findOrFail($id);
        app(LockResult::class)->handle($r, $this->school->id);
        $this->message = 'Result lock হয়েছে।';
    }

    public function publish(int $id): void
    {
        $r = Result::where('school_id', $this->school->id)->findOrFail($id);
        app(PublishResult::class)->handle($r, $this->school->id);
        $this->message = 'Result publish হয়েছে।';
    }

    public function render()
    {
        $results = Result::with(['exam', 'student'])->where('school_id', $this->school->id)
            ->when($this->filterExamId, fn ($q) => $q->where('exam_id', $this->filterExamId))->latest()->get();

        return view('livewire.admin.result-management', ['exams' => Exam::where('school_id', $this->school->id)->get(), 'results' => $results]);
    }
}
