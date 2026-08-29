<?php

namespace App\Livewire\Admin;

use App\Domain\Attendance\Actions\CorrectAttendance;
use App\Domain\Attendance\AttendanceStatus;
use App\Models\School;
use App\Models\StudentAttendance;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AttendanceCorrections extends Component
{
    public School $school;

    public array $statuses = [];

    public string $message = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function correct(int $attendanceId, string $status): void
    {
        $attendance = StudentAttendance::where('school_id', $this->school->id)->whereHas('session', fn ($q) => $q->where('school_id', $this->school->id)->where('status', 'finalized'))->findOrFail($attendanceId);
        app(CorrectAttendance::class)->handle($attendance, $status, auth()->id());
        $this->message = 'উপস্থিতির সংশোধন সংরক্ষণ হয়েছে।';
    }

    public function render()
    {
        $rows = StudentAttendance::with(['student', 'enrollment.academicClass', 'enrollment.section', 'session.teacher', 'session.teacherAssignment.subjectAssignment.subject'])
            ->where('school_id', $this->school->id)->whereHas('session', fn ($q) => $q->where('school_id', $this->school->id)->where('status', 'finalized'))->latest()->get();

        return view('livewire.admin.attendance-corrections', ['rows' => $rows, 'statuses' => AttendanceStatus::values()]);
    }
}
