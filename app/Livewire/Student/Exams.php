<?php

namespace App\Livewire\Student;

use App\Domain\Examination\Actions\StartExamAttempt;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Exams extends Component
{
    public School $school;

    public function mount(School $school, ?Exam $exam = null): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        Student::where(['school_id' => $school->id, 'user_id' => auth()->id(), 'status' => 'active'])->firstOrFail();
        if ($exam) {
            $attempt = app(StartExamAttempt::class)->handle($exam, $school->id);
            redirect()->route('student.attempts.show', ['school' => $school, 'attempt' => $attempt]);
        }
    }

    public function render()
    {
        $student = Student::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();

        return view('livewire.student.exams', ['schedules' => ExamSchedule::with(['exam', 'subject', 'academicClass', 'section'])->where('school_id', $this->school->id)->whereHas('exam', fn ($q) => $q->whereIn('status', ['scheduled', 'ongoing', 'published']))->whereExists(fn ($q) => $q->selectRaw('1')->from('enrollments')->whereColumn('enrollments.academic_year_id', 'exam_schedules.academic_year_id')->whereColumn('enrollments.class_id', 'exam_schedules.class_id')->whereColumn('enrollments.section_id', 'exam_schedules.section_id')->where('enrollments.student_id', $student->id)->where('enrollments.status', 'active'))->get()]);
    }
}
