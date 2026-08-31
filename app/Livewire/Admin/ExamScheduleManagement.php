<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateExamSchedule;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Section;
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

    public function updatedFormAcademicYearId(): void
    {
        $this->resetScheduleDependents('class_id');
    }

    public function updatedFormClassId(): void
    {
        $this->resetScheduleDependents('section_id');
    }

    public function updatedFormTeacherAssignmentId($value): void
    {
        $assignment = TeacherAssignment::where('school_id', $this->school->id)->find($value);
        if (! $assignment) {
            $this->form['teacher_id'] = null;
            $this->form['subject_assignment_id'] = null;
            $this->form['subject_id'] = null;

            return;
        }
        $this->form['teacher_id'] = $assignment->teacher_id;
        $this->form['subject_assignment_id'] = $assignment->subject_assignment_id;
        $this->form['subject_id'] = $assignment->subjectAssignment?->subject_id;
        $this->form['group_id'] = $assignment->group_id;
    }

    private function resetScheduleDependents(string $from): void
    {
        $keys = ['class_id', 'section_id', 'subject_id', 'subject_assignment_id', 'teacher_assignment_id', 'teacher_id', 'group_id'];
        foreach (array_slice($keys, array_search($from, $keys)) as $key) {
            $this->form[$key] = null;
        }
    }

    public function render()
    {
        $classId = $this->form['class_id'] ?? null;
        $yearId = $this->form['academic_year_id'] ?? null;
        return view('livewire.admin.exam-schedules', ['years' => AcademicYear::where('school_id', $this->school->id)->get(), 'classes' => AcademicClass::where('school_id', $this->school->id)->get(), 'subjects' => Subject::where('school_id', $this->school->id)->get(), 'sections' => $classId ? Section::where('school_id', $this->school->id)->where('class_id', $classId)->get() : collect(), 'assignments' => TeacherAssignment::with(['teacher', 'subjectAssignment.subject', 'section', 'academicClass'])->where('school_id', $this->school->id)->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->when($classId, fn ($q) => $q->where('class_id', $classId))->get(), 'schedules' => ExamSchedule::with(['academicClass', 'section', 'subject', 'teacher'])->where(['school_id' => $this->school->id, 'exam_id' => $this->exam->id])->get()]);
    }
}
