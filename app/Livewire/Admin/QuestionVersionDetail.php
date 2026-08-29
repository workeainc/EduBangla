<?php

namespace App\Livewire\Admin;

use App\Models\QuestionVersion;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuestionVersionDetail extends Component
{
    public School $school;

    public QuestionVersion $version;

    public function mount(School $school, QuestionVersion $version): void
    {
        $this->school = $school;
        $this->version = $version->load(['question.bank', 'options']);
        Gate::authorize('update', $school);
        abort_unless($version->school_id === $school->id, 404);
    }

    public function render()
    {
        return view('livewire.admin.question-version-detail');
    }
}
