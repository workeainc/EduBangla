<?php

namespace App\Livewire\Student;

use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReportCards extends Component
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
        $s = Student::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();

        return view('livewire.student.report-cards', ['cards' => ReportCard::with('exam')->where(['school_id' => $this->school->id, 'student_id' => $s->id, 'status' => 'published'])->latest()->get()]);
    }
}
