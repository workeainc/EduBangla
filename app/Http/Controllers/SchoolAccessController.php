<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolAccessController extends Controller
{
    public function index(Request $request): View
    {
        $memberships = $request->user()->schoolMemberships()
            ->where('status', SchoolUser::STATUS_ACTIVE)
            ->with('school')
            ->orderBy('school_id')
            ->get();

        return view('schools.index', compact('memberships'));
    }

    public function select(Request $request): RedirectResponse
    {
        $data = $request->validate(['school_id' => ['required', 'integer']]);

        $membership = $request->user()->schoolMemberships()
            ->where('school_id', $data['school_id'])
            ->where('status', SchoolUser::STATUS_ACTIVE)
            ->with('school')
            ->firstOrFail();

        $request->session()->put('active_school_id', $membership->school_id);

        return redirect()->route('schools.dashboard', $membership->school);
    }

    public function dashboard(School $school, Request $request): View
    {
        $membership = $request->user()->schoolMemberships()
            ->where('school_id', $school->id)
            ->where('status', SchoolUser::STATUS_ACTIVE)
            ->firstOrFail();

        $links = match ($membership->role) {
            'school-admin' => [
                'Academic setup' => route('admin.class-groups', $school),
                'Teachers & staff' => route('admin.teachers', $school),
                'Timetable' => route('admin.timetables', $school),
                'Attendance' => route('admin.attendance', $school),
                'Exams' => route('admin.exams', $school),
                'Results' => route('admin.results', $school),
                'Report cards' => route('admin.report-cards', $school),
                'Promotion' => route('admin.promotions', $school),
                'Finance' => route('admin.finance', $school),
                'Notices' => route('admin.notices', $school),
            ],
            'teacher' => $this->teacherLinks($school, $request->user()->id),
            'student' => $this->studentLinks($school, $request->user()->id),
            'staff' => [
                'Notices' => route('staff.notices', $school),
            ],
            default => abort(403),
        };

        return view('schools.dashboard', compact('school', 'membership', 'links'));
    }

    /** @return array<string, string> */
    private function teacherLinks(School $school, int $userId): array
    {
        abort_unless(Teacher::forSchool($school)->where('user_id', $userId)->where('status', SchoolUser::STATUS_ACTIVE)->exists(), 403);

        return [
            'My timetable' => route('teacher.timetable', $school),
            'My assignments' => route('teacher.assignments', $school),
            'Attendance' => route('teacher.attendance', $school),
            'Exams' => route('teacher.exams', $school),
            'Results' => route('teacher.results', $school),
            'Report cards' => route('teacher.report-cards', $school),
            'Notices' => route('teacher.notices', $school),
        ];
    }

    /** @return array<string, string> */
    private function studentLinks(School $school, int $userId): array
    {
        abort_unless(Student::forSchool($school)->where('user_id', $userId)->where('status', SchoolUser::STATUS_ACTIVE)->exists(), 403);

        return [
            'My timetable' => route('student.timetable', $school),
            'Exams' => route('student.exams', $school),
            'Results' => route('student.results', $school),
            'Report cards' => route('student.report-cards', $school),
            'Finance' => route('student.finance', $school),
            'Notices' => route('student.notices', $school),
        ];
    }
}
