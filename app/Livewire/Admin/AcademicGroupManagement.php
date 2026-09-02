<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateAcademicGroup;
use App\Models\AcademicGroup;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicGroupManagement extends Component
{
    public School $school;

    public string $name = '';

    public string $code = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('create', [AcademicGroup::class, $school->id]);
    }

    public function save(): void
    {
        Gate::authorize('create', [AcademicGroup::class, $this->school->id]);
        app(CreateAcademicGroup::class)->handle($this->school, $this->only(['name', 'code']));
        $this->reset(['name', 'code']);
        session()->flash('status', 'Academic group created.');
    }

    public function render()
    {
        return view('livewire.admin.academic-group-management', [
            'groups' => AcademicGroup::forSchool($this->school)->orderBy('name')->get(),
        ]);
    }
}
