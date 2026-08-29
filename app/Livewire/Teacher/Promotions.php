<?php

namespace App\Livewire\Teacher;

use App\Models\Promotion;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Promotions extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        Teacher::where(['school_id' => $school->id, 'user_id' => auth()->id(), 'status' => 'active'])->firstOrFail();
    }

    public function render()
    {
        return view('livewire.teacher.promotions', ['promotions' => Promotion::where('school_id', $this->school->id)->where('status', 'applied')->latest()->get()]);
    }
}
