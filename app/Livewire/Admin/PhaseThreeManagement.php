<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\CreateClassGroup;
use App\Domain\Academic\Actions\CreateSubjectAssignment;
use App\Domain\Teacher\Actions\CreateTeacherAssignment;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PhaseThreeManagement extends Component
{
    public string $screen = 'teachers';

    public array $form = [];

    public ?int $editingId = null;

    public array $filters = [];

    public function mount(string $screen = 'teachers'): void
    {
        $this->screen = $screen;
        $this->form['status'] = 'active';
    }

    public function save(): void
    {
        $school = $this->activeSchoolId();
        abort_unless($school, 403);
        Gate::authorize('update', School::findOrFail($school));
        if ($this->screen === 'teachers') {
            $d = $this->validate(['form.employee_code' => ['required', 'max:255', Rule::unique('teachers', 'employee_code')->where('school_id', $school)->ignore($this->editingId)], 'form.first_name' => 'required', 'form.last_name' => 'nullable', 'form.joining_date' => 'nullable|date', 'form.status' => 'required']);
            $record = $this->editingId ? Teacher::forSchool($school)->findOrFail($this->editingId) : new Teacher(['school_id' => $school]);
            $record->fill($d['form'])->save();
        } elseif ($this->screen === 'staff') {
            $d = $this->validate(['form.employee_code' => ['required', Rule::unique('staff', 'employee_code')->where('school_id', $school)->ignore($this->editingId)], 'form.name' => 'required', 'form.designation' => 'required', 'form.joining_date' => 'nullable|date', 'form.status' => 'required']);
            $record = $this->editingId ? Staff::forSchool($school)->findOrFail($this->editingId) : new Staff(['school_id' => $school]);
            $record->fill($d['form'])->save();
        } elseif ($this->screen === 'class-groups') {
            $d = $this->validate(['form.class_id' => 'required|integer', 'form.group_id' => 'required|integer']);
            app(CreateClassGroup::class)->handle($school, ...array_values($d['form']));
        } elseif ($this->screen === 'subject-assignments') {
            $d = $this->validate(['form.academic_year_id' => 'required|integer', 'form.class_id' => 'required|integer', 'form.subject_id' => 'required|integer', 'form.group_id' => 'nullable|integer']);
            app(CreateSubjectAssignment::class)->handle(array_merge($d['form'], ['school_id' => $school]));
        } else {
            $d = $this->validate(['form.teacher_id' => 'required|integer', 'form.academic_year_id' => 'required|integer', 'form.class_id' => 'required|integer', 'form.section_id' => 'required|integer', 'form.subject_assignment_id' => 'required|integer', 'form.group_id' => 'nullable|integer']);
            app(CreateTeacherAssignment::class)->handle(array_merge($d['form'], ['school_id' => $school]));
        }$this->reset('form', 'editingId');
        $this->form['status'] = 'active';
        session()->flash('status', 'Saved successfully.');
    }

    public function edit(int $id): void
    {
        $school = $this->activeSchoolId();
        $record = $this->screen === 'teachers' ? Teacher::forSchool($school)->findOrFail($id) : Staff::forSchool($school)->findOrFail($id);
        $this->editingId = $record->id;
        $this->form = $record->only($this->screen === 'teachers' ? ['employee_code', 'first_name', 'last_name', 'joining_date', 'status'] : ['employee_code', 'name', 'designation', 'joining_date', 'status']);
    }

    public function toggleStatus(int $id): void
    {
        $school = $this->activeSchoolId();
        $record = $this->screen === 'teachers' ? Teacher::forSchool($school)->findOrFail($id) : Staff::forSchool($school)->findOrFail($id);
        $record->update(['status' => $record->status === 'active' ? 'inactive' : 'active']);
    }

    public function render()
    {
        $s = $this->activeSchoolId();
        $base = ['years' => AcademicYear::forSchool($s)->get(), 'classes' => AcademicClass::forSchool($s)->get(), 'groups' => AcademicGroup::forSchool($s)->get(), 'subjects' => Subject::forSchool($s)->get(), 'teachers' => Teacher::forSchool($s)->get(), 'sections' => Section::forSchool($s)->get(), 'subjectAssignments' => SubjectAssignment::where('school_id', $s)->get()];
        $base['records'] = match ($this->screen) {
            'teachers' => Teacher::forSchool($s)->get(),'staff' => Staff::forSchool($s)->get(),'class-groups' => ClassGroup::with(['academicClass', 'group'])->where('school_id', $s)->get(),'subject-assignments' => SubjectAssignment::with(['academicYear', 'academicClass', 'subject', 'group'])->where('school_id', $s)->when($this->filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))->when($this->filters['class_id'] ?? null, fn ($q, $v) => $q->where('class_id', $v))->when($this->filters['subject_id'] ?? null, fn ($q, $v) => $q->where('subject_id', $v))->when($this->filters['group_id'] ?? null, fn ($q, $v) => $q->where('group_scope', $v))->get(),'teacher-assignments' => TeacherAssignment::with(['teacher', 'academicYear', 'academicClass', 'section', 'subjectAssignment.subject', 'group'])->where('school_id', $s)->when($this->filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))->when($this->filters['teacher_id'] ?? null, fn ($q, $v) => $q->where('teacher_id', $v))->when($this->filters['class_id'] ?? null, fn ($q, $v) => $q->where('class_id', $v))->when($this->filters['section_id'] ?? null, fn ($q, $v) => $q->where('section_id', $v))->when($this->filters['group_id'] ?? null, fn ($q, $v) => $q->where('group_scope', $v))->when($this->filters['subject_id'] ?? null, fn ($q, $v) => $q->whereHas('subjectAssignment', fn ($q) => $q->where('subject_id', $v)))->get(),default => collect()
        };

        $base['sections'] = isset($this->form['class_id']) ? Section::forSchool($s)->where('class_id', $this->form['class_id'])->get() : collect();
        $base['groups'] = isset($this->form['class_id']) ? AcademicGroup::forSchool($s)->whereIn('id', ClassGroup::where('school_id', $s)->where('class_id', $this->form['class_id'])->pluck('group_id'))->get() : collect();
        $base['subjectAssignments'] = isset($this->form['class_id'], $this->form['academic_year_id']) ? SubjectAssignment::where('school_id', $s)->where('class_id', $this->form['class_id'])->where('academic_year_id', $this->form['academic_year_id'])->get() : collect();

        return view('livewire.admin.phase-three-management', $base);
    }

    private function activeSchoolId(): int
    {
        $school = (int) session('active_school_id');
        abort_unless($school && School::find($school)?->hasActiveMember(auth()->user()), 403);

        return $school;
    }
}
