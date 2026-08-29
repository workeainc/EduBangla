<?php

namespace App\Livewire\Teacher;

use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Exams extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('view', $school);
    }

    public function render()
    {
        $t = Teacher::where('school_id', $this->school->id)->where('user_id', auth()->id())->firstOrFail();

        return view('livewire.teacher.exams', ['schedules' => ExamSchedule::with(['exam', 'subject', 'academicClass', 'section'])->where('school_id', $this->school->id)->where('teacher_id', $t->id)->latest('scheduled_date')->get()]);
    }
}
