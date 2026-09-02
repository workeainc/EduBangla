<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateSection;
use App\Models\AcademicClass;
use App\Models\School;
use App\Models\Section;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicSectionManagement extends Component
{
    public School $school;

    public int|string|null $class_id = null;

    public string $name = '';

    public string $code = '';

    public int|string|null $capacity = null;

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('create', [Section::class, $school->id]);
    }

    public function save(): void
    {
        Gate::authorize('create', [Section::class, $this->school->id]);
        app(CreateSection::class)->handle($this->school, $this->only(['class_id', 'name', 'code', 'capacity']));
        $this->reset(['class_id', 'name', 'code', 'capacity']);
        session()->flash('status', 'Section created.');
    }

    public function render()
    {
        return view('livewire.admin.academic-section-management', [
            'classes' => AcademicClass::forSchool($this->school)->orderBy('sort_order')->orderBy('name')->get(),
            'sections' => Section::with('academicClass')->forSchool($this->school)->orderBy('class_id')->orderBy('name')->get(),
        ]);
    }
}
