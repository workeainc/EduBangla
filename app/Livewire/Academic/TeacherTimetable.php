<?php

namespace App\Livewire\Academic;

use App\Domain\Academic\Queries\TeacherTimetableQuery;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class TeacherTimetable extends Component
{
    public School $school;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
    }

    public function render()
    {
        return view('livewire.academic.teacher-timetable', ['slots' => app(TeacherTimetableQuery::class)->for($this->school->id, auth()->id())]);
    }
}
