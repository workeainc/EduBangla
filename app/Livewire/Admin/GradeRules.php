<?php

namespace App\Livewire\Admin;

use App\Models\GradeRule;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class GradeRules extends Component
{
    public School $school;

    public $name = '';

    public $minimum_percentage = '';

    public $maximum_percentage = '';

    public $letter_grade = '';

    public $grade_point = '';

    public $is_pass = true;

    public $sort_order = 0;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function save()
    {
        $this->validate(['name' => 'required|string|max:100', 'minimum_percentage' => 'required|numeric|min:0|max:100', 'maximum_percentage' => 'required|numeric|gte:minimum_percentage|max:100', 'letter_grade' => 'required|string|max:10', 'grade_point' => 'required|numeric|min:0|max:10']);
        $overlap = GradeRule::where('school_id', $this->school->id)->where('active', true)->where(function ($q) {
            $q->whereBetween('minimum_percentage', [$this->minimum_percentage, $this->maximum_percentage])->orWhereBetween('maximum_percentage', [$this->minimum_percentage, $this->maximum_percentage]);
        })->exists();
        if ($overlap) {
            $this->addError('minimum_percentage', 'Grade range overlaps an active rule.');

            return;
        } GradeRule::create(['school_id' => $this->school->id, 'name' => $this->name, 'minimum_percentage' => $this->minimum_percentage, 'maximum_percentage' => $this->maximum_percentage, 'letter_grade' => $this->letter_grade, 'grade_point' => $this->grade_point, 'is_pass' => $this->is_pass, 'sort_order' => $this->sort_order, 'active' => true]);
        $this->reset(['name', 'minimum_percentage', 'maximum_percentage', 'letter_grade', 'grade_point']);
    }

    public function toggle($id)
    {
        $r = GradeRule::where('school_id', $this->school->id)->findOrFail($id);
        $r->update(['active' => ! $r->active]);
    }

    public function render()
    {
        return view('livewire.admin.grade-rules', ['rules' => GradeRule::where('school_id', $this->school->id)->orderBy('sort_order')->get()]);
    }
}
