<?php

namespace App\Livewire\Teacher;

use App\Models\Result;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReportCards extends Component
{
    public School $school;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        Teacher::where(['school_id' => $school->id, 'user_id' => auth()->id(), 'status' => 'active'])->firstOrFail();
    }

    public function render()
    {
        $teacher = Teacher::where(['school_id' => $this->school->id, 'user_id' => auth()->id()])->firstOrFail();

        return view('livewire.teacher.results', ['results' => Result::with(['exam', 'student', 'items.subject', 'items.schedule'])->where('school_id', $this->school->id)->whereIn('status', ['locked', 'published'])->whereHas('items.schedule', fn ($q) => $q->where('teacher_id', $teacher->id))->latest()->get()]);
    }
}
