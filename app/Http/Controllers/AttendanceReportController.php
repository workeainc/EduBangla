<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceReportController extends Controller
{
    private function school(School $school): void
    {
        Gate::authorize('update', $school);
    }

    public function daily(Request $request, School $school)
    {
        $this->school($school);
        $q = StudentAttendance::with(['student', 'enrollment.academicClass', 'enrollment.section', 'session.teacher', 'session.teacherAssignment.subjectAssignment.subject'])->where('school_id', $school->id);
        if ($request->filled('date')) {
            $q->whereHas('session', fn ($x) => $x->whereDate('attendance_date', $request->date));
        } if ($request->filled('academic_year_id')) {
            $q->whereHas('session', fn ($x) => $x->where('academic_year_id', $request->academic_year_id));
        } if ($request->filled('class_id')) {
            $q->whereHas('session', fn ($x) => $x->where('class_id', $request->class_id));
        } if ($request->filled('section_id')) {
            $q->whereHas('session', fn ($x) => $x->where('section_id', $request->section_id));
        } $rows = $q->get();

        return view('attendance.reports.daily', compact('school', 'rows'));
    }

    public function monthly(Request $request, School $school)
    {
        $this->school($school);
        $month = $request->input('month', now()->format('Y-m'));
        $range = [Carbon::createFromFormat('Y-m', $month)->startOfMonth(), Carbon::createFromFormat('Y-m', $month)->endOfMonth()];
        $aggregates = StudentAttendance::join('attendance_sessions', 'attendance_sessions.id', '=', 'student_attendance.attendance_session_id')->where('student_attendance.school_id', $school->id)->whereBetween('attendance_sessions.attendance_date', $range)->selectRaw("student_attendance.student_id,SUM(status='present') present,SUM(status='absent') absent,SUM(status='late') late,SUM(status='excused') excused,COUNT(*) total")->groupBy('student_attendance.student_id')->get()->keyBy('student_id');
        $rows = Student::where('school_id', $school->id)->get()->map(function ($s) use ($aggregates) {
            $a = $aggregates->get($s->id) ?? (object) ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
            $d = (int) $a->total;

            return compact('s', 'a', 'd');
        });

        return view('attendance.reports.monthly', compact('school', 'rows', 'month'));
    }

    public function class(Request $request, School $school)
    {
        $this->school($school);
        $aggregates = StudentAttendance::join('attendance_sessions', 'attendance_sessions.id', '=', 'student_attendance.attendance_session_id')->where('student_attendance.school_id', $school->id)->when($request->academic_year_id, fn ($q) => $q->where('attendance_sessions.academic_year_id', $request->academic_year_id))->when($request->class_id, fn ($q) => $q->where('attendance_sessions.class_id', $request->class_id))->when($request->section_id, fn ($q) => $q->where('attendance_sessions.section_id', $request->section_id))->selectRaw("student_attendance.student_id,SUM(status='present') present,SUM(status='absent') absent,SUM(status='late') late,SUM(status='excused') excused,COUNT(*) total")->groupBy('student_attendance.student_id')->get()->keyBy('student_id');
        $rows = Student::where('school_id', $school->id)->get()->map(function ($s) use ($aggregates) {
            $a = $aggregates->get($s->id) ?? (object) ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
            $d = (int) $a->total;

            return compact('s', 'a', 'd');
        });

        return view('attendance.reports.class', compact('school', 'rows'));
    }

    public function student(School $school, Student $student)
    {
        $this->school($school);
        abort_unless($student->school_id === $school->id, 404);
        $rows = StudentAttendance::with(['session.teacher', 'session.teacherAssignment.subjectAssignment.subject', 'enrollment.academicYear', 'enrollment.academicClass', 'enrollment.section'])->where(['school_id' => $school->id, 'student_id' => $student->id])->latest()->get();

        return view('attendance.reports.student', compact('school', 'student', 'rows'));
    }
}
