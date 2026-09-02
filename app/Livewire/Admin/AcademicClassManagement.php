<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateAcademicClass;
use App\Models\AcademicClass;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicClassManagement extends Component
{
    public School $school;

    public string $name = '';

    public string $code = '';

    public int|string $sort_order = 0;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('create', [AcademicClass::class, $school->id]);
    }

    public function save(): void
    {
        Gate::authorize('create', [AcademicClass::class, $this->school->id]);
        app(CreateAcademicClass::class)->handle($this->school, $this->only(['name', 'code', 'sort_order']));
        $this->reset(['name', 'code']);
        $this->sort_order = 0;
        session()->flash('status', 'Class created.');
    }

    public function render()
    {
        return view('livewire.admin.academic-class-management', [
            'classes' => AcademicClass::forSchool($this->school)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
