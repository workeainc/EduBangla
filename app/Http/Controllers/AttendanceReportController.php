<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttendanceReportController extends Controller
{
    private function school(School $school): void
    {
        Gate::authorize('update', $school);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request, School $school, bool $includeDate = false, bool $includeMonth = false): array
    {
        $rules = [
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $school->id)],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')->where('school_id', $school->id)],
        ];
        if ($includeDate) {
            $rules['date'] = ['nullable', 'date'];
        }
        if ($includeMonth) {
            $rules['month'] = ['nullable', 'date_format:Y-m'];
        }

        $filters = $request->validate($rules);
        if (isset($filters['section_id']) && isset($filters['class_id']) && ! Section::forSchool($school)->whereKey($filters['section_id'])->where('class_id', $filters['class_id'])->exists()) {
            abort(422, 'The selected section does not belong to the selected class.');
        }

        return $filters;
    }

    private function options(School $school): array
    {
        return [
            'years' => \App\Models\AcademicYear::forSchool($school)->orderBy('name')->get(),
            'classes' => \App\Models\AcademicClass::forSchool($school)->orderBy('sort_order')->get(),
            'sections' => Section::forSchool($school)->with('academicClass')->orderBy('name')->get(),
        ];
    }

    public function daily(Request $request, School $school)
    {
        $this->school($school);
        $filters = $this->filters($request, $school, includeDate: true);
        $q = StudentAttendance::with(['student', 'enrollment.academicClass', 'enrollment.section', 'session.teacher', 'session.teacherAssignment.subjectAssignment.subject'])->where('school_id', $school->id);
        if (! empty($filters['date'])) {
            $q->whereHas('session', fn ($x) => $x->whereDate('attendance_date', $filters['date']));
        } if (! empty($filters['academic_year_id'])) {
            $q->whereHas('session', fn ($x) => $x->where('academic_year_id', $filters['academic_year_id']));
        } if (! empty($filters['class_id'])) {
            $q->whereHas('session', fn ($x) => $x->where('class_id', $filters['class_id']));
        } if (! empty($filters['section_id'])) {
            $q->whereHas('session', fn ($x) => $x->where('section_id', $filters['section_id']));
        } $rows = $q->get();

        return view('attendance.reports.daily', compact('school', 'rows') + $this->options($school));
    }

    public function monthly(Request $request, School $school)
    {
        $this->school($school);
        $filters = $this->filters($request, $school, includeMonth: true);
        $month = $filters['month'] ?? now()->format('Y-m');
        $range = [Carbon::createFromFormat('Y-m', $month)->startOfMonth(), Carbon::createFromFormat('Y-m', $month)->endOfMonth()];
        $aggregates = StudentAttendance::join('attendance_sessions', 'attendance_sessions.id', '=', 'student_attendance.attendance_session_id')
            ->where('student_attendance.school_id', $school->id)
            ->whereBetween('attendance_sessions.attendance_date', $range)
            ->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.academic_year_id', $id))
            ->when($filters['class_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.class_id', $id))
            ->when($filters['section_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.section_id', $id))
            ->selectRaw("student_attendance.student_id,SUM(student_attendance.status='present') present,SUM(student_attendance.status='absent') absent,SUM(student_attendance.status='late') late,SUM(student_attendance.status='excused') excused,COUNT(*) total")
            ->groupBy('student_attendance.student_id')->get()->keyBy('student_id');
        $rows = Student::where('school_id', $school->id)->get()->map(function ($s) use ($aggregates) {
            $a = $aggregates->get($s->id) ?? (object) ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
            $d = (int) $a->total;

            $percentage = $d ? round((((int) $a->present + (int) $a->late) / $d) * 100, 2) : 0;

            return compact('s', 'a', 'd', 'percentage');
        });

        return view('attendance.reports.monthly', compact('school', 'rows', 'month') + $this->options($school));
    }

    public function class(Request $request, School $school)
    {
        $this->school($school);
        $filters = $this->filters($request, $school);
        $aggregates = StudentAttendance::join('attendance_sessions', 'attendance_sessions.id', '=', 'student_attendance.attendance_session_id')->where('student_attendance.school_id', $school->id)->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.academic_year_id', $id))->when($filters['class_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.class_id', $id))->when($filters['section_id'] ?? null, fn ($q, $id) => $q->where('attendance_sessions.section_id', $id))->selectRaw("student_attendance.student_id,SUM(student_attendance.status='present') present,SUM(student_attendance.status='absent') absent,SUM(student_attendance.status='late') late,SUM(student_attendance.status='excused') excused,COUNT(*) total")->groupBy('student_attendance.student_id')->get()->keyBy('student_id');
        $rows = Student::where('school_id', $school->id)->get()->map(function ($s) use ($aggregates) {
            $a = $aggregates->get($s->id) ?? (object) ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
            $d = (int) $a->total;

            $percentage = $d ? round((((int) $a->present + (int) $a->late) / $d) * 100, 2) : 0;

            return compact('s', 'a', 'd', 'percentage');
        });

        return view('attendance.reports.class', compact('school', 'rows') + $this->options($school));
    }

    public function student(School $school, Student $student)
    {
        $this->school($school);
        abort_unless($student->school_id === $school->id, 404);
        $rows = StudentAttendance::with(['session.teacher', 'session.teacherAssignment.subjectAssignment.subject', 'enrollment.academicYear', 'enrollment.academicClass', 'enrollment.section'])->where(['school_id' => $school->id, 'student_id' => $student->id])->latest()->get();

        return view('attendance.reports.student', compact('school', 'student', 'rows'));
    }
}
