<?php

namespace App\Livewire\Admin;

use App\Domain\Promotion\Actions\ApplyPromotion;
use App\Domain\Promotion\Actions\ApprovePromotion;
use App\Domain\Promotion\Actions\CancelPromotion;
use App\Domain\Promotion\Actions\EvaluatePromotion;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Promotions extends Component
{
    public School $school;

    public $student_id;

    public $source_enrollment_id;

    public $academic_year_id;

    public $source_class_id;

    public $source_section_id;

    public $target_academic_year_id;

    public $target_class_id;

    public $target_section_id;

    public ?Promotion $promotion = null;

    public function mount(School $school, ?Promotion $promotion = null)
    {
        $this->school = $school;
        if ($promotion && $promotion->school_id !== $school->id) {
            abort(404);
        }
        $this->promotion = $promotion;
        Gate::authorize('update', $school);
    }

    public function evaluate($id)
    {
        $p = Promotion::where('school_id', $this->school->id)->findOrFail($id);
        app(EvaluatePromotion::class)->handle($p, $this->school->id);
    }

    public function save(): void
    {
        $this->validate(['student_id' => 'required|exists:students,id', 'source_enrollment_id' => 'required|exists:enrollments,id', 'academic_year_id' => 'required|exists:academic_years,id', 'source_class_id' => 'required|exists:classes,id', 'target_academic_year_id' => 'required|exists:academic_years,id', 'target_class_id' => 'required|exists:classes,id']);
        $valid = Enrollment::where(['id' => $this->source_enrollment_id, 'school_id' => $this->school->id, 'student_id' => $this->student_id, 'academic_year_id' => $this->academic_year_id, 'class_id' => $this->source_class_id, 'section_id' => $this->source_section_id])->exists();
        abort_unless($valid, 422);
        Promotion::create(['school_id' => $this->school->id, 'student_id' => $this->student_id, 'source_enrollment_id' => $this->source_enrollment_id, 'academic_year_id' => $this->academic_year_id, 'source_class_id' => $this->source_class_id, 'source_section_id' => $this->source_section_id, 'target_academic_year_id' => $this->target_academic_year_id, 'target_class_id' => $this->target_class_id, 'target_section_id' => $this->target_section_id, 'status' => 'draft']);
    }

    public function approve($id)
    {
        $p = Promotion::where('school_id', $this->school->id)->findOrFail($id);
        app(ApprovePromotion::class)->handle($p, $this->school->id);
    }

    public function apply($id)
    {
        $p = Promotion::where('school_id', $this->school->id)->findOrFail($id);
        app(ApplyPromotion::class)->handle($p, $this->school->id);
    }

    public function cancel($id)
    {
        $p = Promotion::where('school_id', $this->school->id)->findOrFail($id);
        app(CancelPromotion::class)->handle($p, $this->school->id);
    }

    public function render()
    {
        return view('livewire.admin.promotions', ['promotions' => Promotion::with(['student', 'targetClass', 'targetAcademicYear'])->where('school_id', $this->school->id)->latest()->get()]);
    }
}
