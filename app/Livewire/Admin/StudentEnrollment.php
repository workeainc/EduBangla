<?php

namespace App\Livewire\Admin;

use App\Domain\Student\Actions\AttachGuardian;
use App\Domain\Student\Actions\CreateEnrollment;
use App\Domain\Student\Actions\CreateGuardian;
use App\Domain\Student\Actions\CreateStudent;
use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StudentEnrollment extends Component
{
    public School $school;

    public array $student = [];
    public array $guardian = [];
    public string $guardianMode = 'new';
    public $guardian_id;
    public $relationship_type = 'parent';
    public bool $is_primary = true;
    public $academic_year_id;
    public $class_id;
    public $section_id;
    public $group_id;
    public $roll;
    public string $message = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('view', $school);
        Gate::authorize('create', [Student::class, $school->id]);
        $this->student['status'] = 'active';
    }

    public function updatedAcademicYearId(): void
    {
        $this->class_id = $this->section_id = $this->group_id = null;
    }

    public function updatedClassId(): void
    {
        $this->section_id = $this->group_id = null;
    }

    public function updatedGuardianMode(): void
    {
        $this->guardian_id = null;
        $this->resetErrorBag('guardian_id');
    }

    public function submit(): void
    {
        $this->message = '';
        Gate::authorize('create', [Student::class, $this->school->id]);
        $this->validate([
            'student.student_code' => ['required', 'string', 'max:64'],
            'student.first_name' => ['required', 'string', 'max:255'],
            'student.last_name' => ['nullable', 'string', 'max:255'],
            'student.date_of_birth' => ['nullable', 'date'],
            'student.gender' => ['nullable', 'string', 'max:20'],
            'student.phone' => ['nullable', 'string', 'max:32'],
            'student.email' => ['nullable', 'email', 'max:255'],
            'student.address' => ['nullable', 'string'],
            'student.admission_date' => ['nullable', 'date'],
            'guardianMode' => ['required', 'in:new,existing'],
            'guardian.name' => ['required_if:guardianMode,new', 'string', 'max:255'],
            'guardian.phone' => ['required_if:guardianMode,new', 'string', 'max:32'],
            'guardian.email' => ['nullable', 'email', 'max:255'],
            'guardian.address' => ['nullable', 'string'],
            'guardian_id' => ['nullable', 'integer'],
            'relationship_type' => ['required', 'string', 'max:32'],
            'academic_year_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'roll' => ['required', 'integer', 'min:1'],
        ]);

        if ($this->guardianMode === 'existing' && ! $this->guardian_id) {
            $this->addError('guardian_id', 'Choose an existing guardian.');
            return;
        }

        Gate::authorize('create', [Guardian::class, $this->school->id]);
        Gate::authorize('create', [Enrollment::class, $this->school->id]);

        try {
            $student = DB::transaction(function () {
                $student = app(CreateStudent::class)->handle($this->school->id, $this->student);
                $guardian = $this->guardianMode === 'existing'
                    ? Guardian::forSchool($this->school)->findOrFail($this->guardian_id)
                    : app(CreateGuardian::class)->handle($this->school->id, $this->guardian);

                app(AttachGuardian::class)->handle($student, $guardian, $this->relationship_type, $this->is_primary);
                app(CreateEnrollment::class)->handle([
                    'school_id' => $this->school->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $this->academic_year_id,
                    'class_id' => $this->class_id,
                    'section_id' => $this->section_id,
                    'group_id' => $this->group_id,
                    'roll' => $this->roll,
                    'status' => 'active',
                    'enrolled_at' => now()->toDateString(),
                ]);

                return $student;
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }
            return;
        } catch (ModelNotFoundException) {
            $this->addError('form', 'One or more selected records are no longer available in this school. Please review the selections and try again.');
            return;
        }

        $this->reset('student', 'guardian', 'guardian_id', 'academic_year_id', 'class_id', 'section_id', 'group_id', 'roll');
        $this->student['status'] = 'active';
        $this->message = 'Student, guardian, and enrollment created successfully.';
        $this->dispatch('student-enrollment-created', studentId: $student->id);
    }

    public function render()
    {
        $schoolId = $this->school->id;
        $classes = AcademicClass::forSchool($schoolId)->orderBy('sort_order')->get();
        return view('livewire.admin.student-enrollment', [
            'years' => AcademicYear::forSchool($schoolId)->orderBy('name')->get(),
            'classes' => $classes,
            'sections' => $this->class_id ? Section::forSchool($schoolId)->where('class_id', $this->class_id)->orderBy('name')->get() : collect(),
            'groups' => $this->class_id ? AcademicGroup::forSchool($schoolId)->whereIn('id', DB::table('class_groups')->where('school_id', $schoolId)->where('class_id', $this->class_id)->pluck('group_id'))->orderBy('name')->get() : collect(),
            'guardians' => Guardian::forSchool($schoolId)->where('status', 'active')->orderBy('name')->get(),
            'students' => Student::forSchool($schoolId)->withCount('enrollments')->latest()->get(),
        ]);
    }
}
