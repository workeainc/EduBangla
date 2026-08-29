<?php

namespace App\Livewire\Teacher;

use App\Domain\Examination\Actions\EnterExamMark;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamMarks extends Component
{
    public School $school;

    public Exam $exam;

    public array $marks = [];

    public string $message = '';

    public function mount(School $school, Exam $exam)
    {
        $this->school = $school;
        $this->exam = $exam;
        Gate::authorize('view', $school);
        abort_unless($exam->school_id === $school->id, 404);
        $teacher = Teacher::where(['school_id' => $school->id, 'user_id' => auth()->id()])->firstOrFail();
        abort_unless(ExamSchedule::where(['school_id' => $school->id, 'exam_id' => $exam->id, 'teacher_id' => $teacher->id])->exists(), 403);
    }

    public function save(int $scheduleId)
    {
        $t = Teacher::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();
        $s = ExamSchedule::where(['school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'teacher_id' => $t->id])->findOrFail($scheduleId);
        foreach (Enrollment::where(['school_id' => $this->school->id, 'academic_year_id' => $s->academic_year_id, 'class_id' => $s->class_id, 'section_id' => $s->section_id, 'status' => 'active'])->get() as $e) {
            if (isset($this->marks[$e->student_id])) {
                app(EnterExamMark::class)->handle(['school_id' => $this->school->id, 'exam_schedule_id' => $s->id, 'student_id' => $e->student_id, 'enrollment_id' => $e->id, 'teacher_id' => $t->id, 'entered_by' => auth()->id(), 'marks' => (int) $this->marks[$e->student_id], 'maximum_marks' => $s->maximum_marks]);
            }
        }$this->message = 'নম্বর সংরক্ষণ হয়েছে।';
    }

    public function render()
    {
        $t = Teacher::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();
        $schedules = ExamSchedule::with(['subject', 'academicClass', 'section'])->where(['school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'teacher_id' => $t->id])->get();

        return view('livewire.teacher.exam-marks', compact('schedules'));
    }
}
