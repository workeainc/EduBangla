<?php

namespace App\Livewire\Student;

use App\Models\Promotion;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Promotions extends Component
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

        return view('livewire.student.promotions', ['promotions' => Promotion::with(['targetClass', 'targetAcademicYear'])->where(['school_id' => $this->school->id, 'student_id' => $s->id, 'status' => 'applied'])->latest()->get()]);
    }
}
