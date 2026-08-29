<?php

namespace App\Livewire\Admin;

use App\Domain\Result\Actions\GenerateReportCard;
use App\Domain\Result\Actions\PublishReportCard;
use App\Models\ReportCard;
use App\Models\Result;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReportCards extends Component
{
    public School $school;

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function generate($resultId)
    {
        $r = Result::where('school_id', $this->school->id)->findOrFail($resultId);
        app(GenerateReportCard::class)->handle($r, $this->school->id);
    }

    public function publish($id)
    {
        $c = ReportCard::where('school_id', $this->school->id)->findOrFail($id);
        app(PublishReportCard::class)->handle($c, $this->school->id);
    }

    public function render()
    {
        return view('livewire.admin.report-cards', ['results' => Result::where('school_id', $this->school->id)->whereIn('status', ['locked', 'published'])->latest()->get(), 'cards' => ReportCard::with(['exam', 'student'])->where('school_id', $this->school->id)->latest()->get()]);
    }
}
