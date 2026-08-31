<?php

namespace App\Livewire\Teacher;

use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Livewire\Component;

class MyAssignments extends Component
{
    public function render()
    {
        $school = (int) session('active_school_id');
        abort_unless($school && School::find($school)?->hasActiveMember(auth()->user()), 403);
        $teacher = Teacher::where('school_id', $school)->where('user_id', auth()->id())->firstOrFail();

        return view('livewire.teacher.my-assignments', ['teacher' => $teacher, 'assignments' => TeacherAssignment::with(['academicYear', 'academicClass', 'section', 'group', 'subjectAssignment.subject'])->where('school_id', $school)->where('teacher_id', $teacher->id)->orderByDesc('academic_year_id')->get()]);
    }
}
