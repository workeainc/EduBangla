<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateSubject;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AcademicSubjectManagement extends Component
{
    public School $school;

    public string $name = '';

    public string $code = '';

    public string $short_name = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('create', [Subject::class, $school->id]);
    }

    public function save(): void
    {
        Gate::authorize('create', [Subject::class, $this->school->id]);
        app(CreateSubject::class)->handle($this->school, $this->only(['name', 'code', 'short_name']));
        $this->reset(['name', 'code', 'short_name']);
        session()->flash('status', 'Subject created.');
    }

    public function render()
    {
        return view('livewire.admin.academic-subject-management', [
            'subjects' => Subject::forSchool($this->school)->orderBy('name')->get(),
        ]);
    }
}
