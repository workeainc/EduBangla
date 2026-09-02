<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\ActivateAcademicYear;
use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicYearManagement extends Component
{
    public School $school;

    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('create', [AcademicYear::class, $school->id]);
    }

    public function save(): void
    {
        Gate::authorize('create', [AcademicYear::class, $this->school->id]);
        app(CreateAcademicYear::class)->handle($this->school, $this->only(['name', 'start_date', 'end_date']));
        $this->reset(['name', 'start_date', 'end_date']);
        session()->flash('status', 'Academic year created as a draft.');
    }

    public function activate(int $id): void
    {
        $year = AcademicYear::forSchool($this->school)->findOrFail($id);
        Gate::authorize('update', $year);
        app(ActivateAcademicYear::class)->handle($year);
        session()->flash('status', 'Academic year activated.');
    }

    public function render()
    {
        return view('livewire.admin.academic-year-management', [
            'years' => AcademicYear::forSchool($this->school)->orderByDesc('start_date')->get(),
        ]);
    }
}
