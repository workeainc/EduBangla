<?php

namespace App\Livewire\Teacher;

use App\Domain\School\TenantContext;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Livewire\Component;

class MyAssignments extends Component
{
    public function render()
    {
        $school = app(TenantContext::class)->id();
        $teacher = Teacher::where('school_id', $school)->where('user_id', auth()->id())->firstOrFail();

        return view('livewire.teacher.my-assignments', ['teacher' => $teacher, 'assignments' => TeacherAssignment::where('school_id', $school)->where('teacher_id', $teacher->id)->get()]);
    }
}
