<?php

namespace App\Livewire\Student;

use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Results extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        Student::where(['school_id' => $school->id, 'user_id' => auth()->id(), 'status' => 'active'])->firstOrFail();
    }

    public function render()
    {
        $student = Student::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();

        return view('livewire.student.results', ['results' => Result::with(['exam', 'items.subject'])->where(['school_id' => $this->school->id, 'student_id' => $student->id, 'status' => 'published'])->latest()->get()]);
    }
}
