<?php

namespace App\Livewire\Attendance;

use App\Domain\Attendance\Actions\CreateAttendanceSession;
use App\Domain\Attendance\Actions\FinalizeAttendance;
use App\Domain\Attendance\Actions\RecordAttendance;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Management extends Component
{
    public School $school;

    public string $role = 'teacher';

    public ?int $assignmentId = null;

    public ?int $sessionId = null;

    public ?int $yearId = null;

    public ?int $classId = null;

    public ?int $sectionId = null;

    public string $date = '';

    public string $period = 'regular';

    public array $statuses = [];

    public string $message = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        $this->role = SchoolUser::where(['school_id' => $school->id, 'user_id' => auth()->id(), 'role' => 'school-admin', 'status' => 'active'])->exists() ? 'admin' : 'teacher';
        $this->date = now()->toDateString();
        Gate::authorize('view', $school);
    }

    public function loadStudents(): void
    {
        $this->validate(['assignmentId' => 'required|integer', 'date' => 'required|date']);
        $a = $this->assignment();
        $this->yearId = $a->academic_year_id;
        $this->classId = $a->class_id;
        $this->sectionId = $a->section_id;
        $this->statuses = Enrollment::where('school_id', $this->school->id)->where('academic_year_id', $a->academic_year_id)->where('class_id', $a->class_id)->where('section_id', $a->section_id)->get()->mapWithKeys(fn ($e) => [$e->student_id => 'present'])->all();
        $this->message = 'শিক্ষার্থীদের তালিকা প্রস্তুত হয়েছে।';
    }

    public function save(): void
    {
        $this->validate(['assignmentId' => 'required', 'date' => 'required|date']);
        $a = $this->assignment();
        $teacher = $a->teacher;
        $session = $this->sessionId ? AttendanceSession::where('school_id', $this->school->id)->findOrFail($this->sessionId) : (new CreateAttendanceSession)->handle(['school_id' => $this->school->id, 'academic_year_id' => $a->academic_year_id, 'class_id' => $a->class_id, 'section_id' => $a->section_id, 'teacher_id' => $teacher->id, 'teacher_assignment_id' => $a->id, 'attendance_date' => $this->date, 'period' => $this->period, 'created_by' => auth()->id()]);
        Gate::authorize('update', $session);
        $rows = [];
        foreach ($this->statuses as $studentId => $status) {
            $e = Enrollment::where(['school_id' => $this->school->id, 'student_id' => $studentId, 'academic_year_id' => $a->academic_year_id, 'class_id' => $a->class_id, 'section_id' => $a->section_id])->firstOrFail();
            $rows[] = ['student_id' => $studentId, 'enrollment_id' => $e->id, 'status' => $status];
        } if ($rows && ! $session->attendances()->exists()) {
            (new RecordAttendance)->handle($session, $rows, auth()->id());
        } $this->sessionId = $session->id;
        $this->message = 'উপস্থিতি সংরক্ষণ হয়েছে।';
    }

    public function finalize(): void
    {
        $session = AttendanceSession::findOrFail($this->sessionId);
        Gate::authorize('finalize', $session);
        (new FinalizeAttendance)->handle($session);
        $this->message = 'উপস্থিতি চূড়ান্ত হয়েছে; এখন এটি read-only।';
    }

    public function presentAll(): void
    {
        foreach ($this->statuses as $id => $v) {
            $this->statuses[$id] = 'present';
        }
    }

    protected function assignment(): TeacherAssignment
    {
        $q = TeacherAssignment::where('school_id', $this->school->id);
        if ($this->role === 'teacher') {
            $t = Teacher::where('school_id', $this->school->id)->where('user_id', auth()->id())->firstOrFail();
            $q->where('teacher_id', $t->id);
        }

        return $q->findOrFail($this->assignmentId);
    }

    public function render()
    {
        $assignments = TeacherAssignment::with(['academicYear', 'academicClass', 'section', 'subjectAssignment.subject', 'teacher'])->where('school_id', $this->school->id)->when($this->role === 'teacher', function ($q) {
            $t = Teacher::where('school_id', $this->school->id)->where('user_id', auth()->id())->first();
            $q->where('teacher_id', $t?->id ?? 0);
        })->get();
        $students = $this->statuses;

        return view('livewire.attendance.management', compact('assignments', 'students'));
    }
}
