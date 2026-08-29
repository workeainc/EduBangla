<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateExamSchedule;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamScheduleManagement extends Component
{
    public School $school;

    public Exam $exam;

    public ?ExamSchedule $schedule = null;

    public array $form = [];

    public string $message = '';

    public function mount(School $school, Exam $exam, ?ExamSchedule $schedule = null)
    {
        $this->school = $school;
        $this->exam = $exam;
        Gate::authorize('update', $school);
        abort_unless($exam->school_id === $school->id, 404);
        if ($schedule) {
            abort_unless($schedule->school_id === $school->id && $schedule->exam_id === $exam->id, 404);
            $this->schedule = $schedule;
            $this->form = $schedule->only(['academic_year_id', 'class_id', 'section_id', 'subject_id', 'subject_assignment_id', 'teacher_assignment_id', 'teacher_id', 'group_id', 'scheduled_date', 'start_time', 'end_time', 'maximum_marks', 'duration_minutes', 'mode']);
        }
    }

    public function save()
    {
        $this->validate(['form.academic_year_id' => 'required', 'form.class_id' => 'required', 'form.section_id' => 'required', 'form.subject_id' => 'required', 'form.subject_assignment_id' => 'required', 'form.teacher_assignment_id' => 'required', 'form.teacher_id' => 'required', 'form.scheduled_date' => 'required|date', 'form.start_time' => 'required', 'form.end_time' => 'required', 'form.maximum_marks' => 'required|integer|min:1', 'form.duration_minutes' => 'required|integer|min:1']);
        app(CreateExamSchedule::class)->handle($this->form + ['school_id' => $this->school->id, 'exam_id' => $this->exam->id] + ($this->schedule ? ['_schedule_id' => $this->schedule->id] : []));
        $this->form = [];
        $this->message = $this->schedule ? 'Schedule আপডেট হয়েছে।' : 'Schedule তৈরি হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.exam-schedules', ['years' => AcademicYear::where('school_id', $this->school->id)->get(), 'classes' => AcademicClass::where('school_id', $this->school->id)->get(), 'subjects' => Subject::where('school_id', $this->school->id)->get(), 'assignments' => TeacherAssignment::with('teacher')->where('school_id', $this->school->id)->get(), 'schedules' => ExamSchedule::with(['academicClass', 'section', 'subject', 'teacher'])->where(['school_id' => $this->school->id, 'exam_id' => $this->exam->id])->get()]);
    }
}
