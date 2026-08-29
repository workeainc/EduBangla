<?php

namespace App\Livewire\Admin;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\PromotionRule;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PromotionRules extends Component
{
    public School $school;

    public $name = '';

    public $academic_year_id;

    public $source_class_id;

    public $target_class_id;

    public $minimum_gpa;

    public $minimum_passed_subjects = 0;

    public $failed_subject_tolerance = 0;

    public $minimum_overall_status = 'pass';

    public ?PromotionRule $rule = null;

    public function mount(School $school, ?PromotionRule $rule = null)
    {
        $this->school = $school;
        if ($rule) {
            abort_unless($rule->school_id === $school->id, 404);
            $this->rule = $rule;
            foreach (['academic_year_id', 'source_class_id', 'target_class_id', 'minimum_gpa', 'minimum_passed_subjects', 'failed_subject_tolerance', 'minimum_overall_status'] as $field) {
                $this->$field = $rule->$field;
            }
        }
        Gate::authorize('update', $school);
    }

    public function save(): void
    {
        $this->validate(['academic_year_id' => 'required|exists:academic_years,id', 'source_class_id' => 'required|exists:classes,id', 'target_class_id' => 'required|exists:classes,id|different:source_class_id', 'minimum_overall_status' => 'required|in:pass', 'minimum_gpa' => 'nullable|numeric|min:0|max:10', 'minimum_passed_subjects' => 'required|integer|min:0', 'failed_subject_tolerance' => 'required|integer|min:0']);
        foreach (['academic_year_id', 'source_class_id', 'target_class_id'] as $field) {
            if (! AcademicYear::where('school_id', $this->school->id)->where('id', $this->$field)->exists() && $field === 'academic_year_id' || ! AcademicClass::where('school_id', $this->school->id)->where('id', $this->$field)->exists() && $field !== 'academic_year_id') {
                $this->addError($field, 'Invalid tenant scope.');

                return;
            }
        } $data = ['school_id' => $this->school->id, 'academic_year_id' => $this->academic_year_id, 'source_class_id' => $this->source_class_id, 'target_class_id' => $this->target_class_id, 'minimum_overall_status' => $this->minimum_overall_status, 'minimum_gpa' => $this->minimum_gpa, 'minimum_passed_subjects' => $this->minimum_passed_subjects, 'failed_subject_tolerance' => $this->failed_subject_tolerance, 'active' => true];
        if ($this->rule) {
            // Never trust a hydrated model identity from the browser; reload it in-tenant.
            $rule = PromotionRule::where('school_id', $this->school->id)->findOrFail($this->rule->id);
            $rule->update($data);
        } else {
            PromotionRule::create($data);
        }
    }

    public function toggle($id)
    {
        $r = PromotionRule::where('school_id', $this->school->id)->findOrFail($id);
        $r->update(['active' => ! $r->active]);
    }

    public function render()
    {
        return view('livewire.admin.promotion-rules', ['rules' => PromotionRule::where('school_id', $this->school->id)->latest()->get(), 'years' => AcademicYear::where('school_id', $this->school->id)->get(), 'classes' => AcademicClass::where('school_id', $this->school->id)->get()]);
    }
}
