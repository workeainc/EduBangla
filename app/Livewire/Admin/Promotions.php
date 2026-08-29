<?php

namespace App\Livewire\Admin;

use App\Domain\Promotion\Actions\ApplyPromotion;
use App\Domain\Promotion\Actions\ApprovePromotion;
use App\Domain\Promotion\Actions\CancelPromotion;
use App\Domain\Promotion\Actions\EvaluatePromotion;
use App\Models\Promotion;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Promotions extends Component
{
    public School $school;

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
