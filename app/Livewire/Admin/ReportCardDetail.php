<?php

namespace App\Livewire\Admin;

use App\Models\ReportCard;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReportCardDetail extends Component
{
    public School $school;

    public ReportCard $reportCard;

    public function mount(School $school, ReportCard $reportCard): void
    {
        abort_unless($reportCard->school_id === $school->id, 404);
        $this->school = $school;
        $this->reportCard = $reportCard->load(['exam', 'student', 'enrollment.academicYear']);
        Gate::authorize('view', $reportCard);
    }

    public function render()
    {
        return view('livewire.admin.report-card-detail');
    }
}
