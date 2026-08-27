<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateClassGroup;
use App\Domain\Academic\Actions\CreateSubjectAssignment;
use App\Domain\School\TenantContext;
use App\Domain\Teacher\Actions\CreateTeacherAssignment;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PhaseThreeManagement extends Component
{
    public string $screen = 'teachers';

    public array $form = [];

    public function mount(string $screen = 'teachers'): void
    {
        $this->screen = $screen;
    }

    public function save(): void
    {
        $school = app(TenantContext::class)->id();
        abort_unless($school, 403);
        if ($this->screen === 'teachers') {
            $d = $this->validate(['form.employee_code' => ['required', 'max:255', Rule::unique('teachers', 'employee_code')->where('school_id', $school)], 'form.first_name' => 'required', 'form.last_name' => 'nullable', 'form.joining_date' => 'nullable|date', 'form.status' => 'required']);
            Teacher::create(array_merge($d['form'], ['school_id' => $school]));
        } elseif ($this->screen === 'staff') {
            $d = $this->validate(['form.employee_code' => ['required', Rule::unique('staff', 'employee_code')->where('school_id', $school)], 'form.name' => 'required', 'form.designation' => 'required', 'form.joining_date' => 'nullable|date', 'form.status' => 'required']);
            Staff::create(array_merge($d['form'], ['school_id' => $school]));
        } elseif ($this->screen === 'class-groups') {
            $d = $this->validate(['form.class_id' => 'required|integer', 'form.group_id' => 'required|integer']);
            app(CreateClassGroup::class)->handle($school, ...array_values($d['form']));
        } elseif ($this->screen === 'subject-assignments') {
            $d = $this->validate(['form.academic_year_id' => 'required|integer', 'form.class_id' => 'required|integer', 'form.subject_id' => 'required|integer', 'form.group_id' => 'nullable|integer']);
            app(CreateSubjectAssignment::class)->handle(array_merge($d['form'], ['school_id' => $school]));
        } else {
            $d = $this->validate(['form.teacher_id' => 'required|integer', 'form.academic_year_id' => 'required|integer', 'form.class_id' => 'required|integer', 'form.section_id' => 'required|integer', 'form.subject_assignment_id' => 'required|integer', 'form.group_id' => 'nullable|integer']);
            app(CreateTeacherAssignment::class)->handle(array_merge($d['form'], ['school_id' => $school]));
        }$this->reset('form');
        session()->flash('status', 'Saved successfully.');
    }

    public function render()
    {
        $s = app(TenantContext::class)->id();
        $base = ['years' => AcademicYear::forSchool($s)->get(), 'classes' => AcademicClass::forSchool($s)->get(), 'groups' => AcademicGroup::forSchool($s)->get(), 'subjects' => Subject::forSchool($s)->get(), 'teachers' => Teacher::forSchool($s)->get(), 'sections' => Section::forSchool($s)->get(), 'subjectAssignments' => SubjectAssignment::where('school_id', $s)->get()];
        $base['records'] = match ($this->screen) {
            'teachers' => Teacher::forSchool($s)->get(),'staff' => Staff::forSchool($s)->get(),'class-groups' => ClassGroup::where('school_id', $s)->get(),'subject-assignments' => SubjectAssignment::where('school_id', $s)->get(),'teacher-assignments' => TeacherAssignment::where('school_id', $s)->get(),default => collect()
        };

        return view('livewire.admin.phase-three-management',$base);
    }
}
