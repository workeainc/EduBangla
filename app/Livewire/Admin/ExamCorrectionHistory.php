<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamCorrectionHistory extends Component
{
    public School $school;

    public Exam $exam;

    public function mount(School $school, Exam $exam): void
    {
        $this->school = $school;
        $this->exam = $exam;
        Gate::authorize('update', $school);
        abort_unless($exam->school_id === $school->id, 404);
    }

    public function render()
    {
        $ids = ExamMark::where('school_id', $this->school->id)->whereHas('schedule', fn ($q) => $q->where('exam_id', $this->exam->id))->pluck('id');
        $logs = AuditLog::with('actor')->where('school_id', $this->school->id)->where('action', 'exam.mark_corrected')->where('auditable_type', ExamMark::class)->whereIn('auditable_id', $ids)->latest()->get();

        return view('livewire.admin.exam-correction-history', ['logs' => $logs]);
    }
}
