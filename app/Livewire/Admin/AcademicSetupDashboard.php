<?php

namespace App\Livewire\Admin;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicSetupDashboard extends Component
{
    public School $school;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function render()
    {
        return view('livewire.admin.academic-setup-dashboard', [
            'counts' => [
                'years' => AcademicYear::forSchool($this->school)->count(),
                'classes' => AcademicClass::forSchool($this->school)->count(),
                'sections' => Section::forSchool($this->school)->count(),
                'subjects' => Subject::forSchool($this->school)->count(),
                'groups' => AcademicGroup::forSchool($this->school)->count(),
            ],
            'activeYear' => AcademicYear::forSchool($this->school)->where('status', 'active')->first(),
        ]);
    }
}
