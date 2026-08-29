<?php

namespace App\Livewire\Admin;

use App\Models\PromotionRule;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PromotionRules extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function toggle($id)
    {
        $r = PromotionRule::where('school_id', $this->school->id)->findOrFail($id);
        $r->update(['active' => ! $r->active]);
    }

    public function render()
    {
        return view('livewire.admin.promotion-rules', ['rules' => PromotionRule::where('school_id', $this->school->id)->latest()->get()]);
    }
}
