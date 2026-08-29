<?php

use App\Http\Controllers\AttendanceReportController;
use App\Livewire\Admin\AttendanceCorrections;
use App\Livewire\Admin\PhaseThreeManagement;
use App\Livewire\Attendance\Management as AttendanceManagement;
use App\Livewire\Teacher\MyAssignments;
use App\Models\School;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => response('Login required', 401))->name('login');

Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/schools/{school}', function (School $school) {
        Gate::authorize('view', $school);

        return response()->json(['id' => $school->id, 'name' => $school->name]);
    })->name('schools.show');

    Route::prefix('schools/{school}/admin')->middleware('school.admin')->group(function () {
        Route::get('/teachers', PhaseThreeManagement::class)->defaults('screen', 'teachers')->name('admin.teachers');
        Route::get('/teachers/create', PhaseThreeManagement::class)->defaults('screen', 'teachers')->name('admin.teachers.create');
        Route::get('/teachers/{teacher}', PhaseThreeManagement::class)->defaults('screen', 'teachers')->name('admin.teachers.show');
        Route::get('/teachers/{teacher}/edit', PhaseThreeManagement::class)->defaults('screen', 'teachers')->name('admin.teachers.edit');
        Route::get('/staff', PhaseThreeManagement::class)->defaults('screen', 'staff')->name('admin.staff');
        Route::get('/staff/create', PhaseThreeManagement::class)->defaults('screen', 'staff')->name('admin.staff.create');
        Route::get('/staff/{staff}', PhaseThreeManagement::class)->defaults('screen', 'staff')->name('admin.staff.show');
        Route::get('/staff/{staff}/edit', PhaseThreeManagement::class)->defaults('screen', 'staff')->name('admin.staff.edit');
        Route::get('/academic/class-groups', PhaseThreeManagement::class)->defaults('screen', 'class-groups')->name('admin.class-groups');
        Route::get('/academic/subject-assignments', PhaseThreeManagement::class)->defaults('screen', 'subject-assignments')->name('admin.subject-assignments');
        Route::get('/academic/teacher-assignments', PhaseThreeManagement::class)->defaults('screen', 'teacher-assignments')->name('admin.teacher-assignments');
    });
    Route::get('schools/{school}/admin/teachers/{teacher}/profile', function (School $school, Teacher $teacher) {
        abort_unless($teacher->school_id === $school->id, 404);

        return view('admin.profile', ['title' => 'Teacher profile', 'person' => $teacher, 'school' => $school, 'assignments' => TeacherAssignment::with(['academicYear', 'academicClass', 'section', 'subjectAssignment.subject'])->where('school_id', $school->id)->where('teacher_id', $teacher->id)->get()]);
    })->middleware('school.admin')->name('admin.teacher.profile');
    Route::get('schools/{school}/admin/staff/{staff}/profile', function (School $school, Staff $staff) {
        abort_unless($staff->school_id === $school->id, 404);

        return view('admin.profile', ['title' => 'Staff profile', 'person' => $staff, 'school' => $school]);
    })->middleware('school.admin')->name('admin.staff.profile');
    Route::get('schools/{school}/teacher/assignments', MyAssignments::class)->name('teacher.assignments');
    Route::get('schools/{school}/teacher/profile', MyAssignments::class)->name('teacher.profile');
    Route::get('schools/{school}/teacher/attendance', AttendanceManagement::class)->name('teacher.attendance')->middleware('teacher');
    Route::get('schools/{school}/admin/attendance', AttendanceManagement::class)->name('admin.attendance')->middleware('school.admin');
    Route::get('schools/{school}/admin/attendance/corrections', AttendanceCorrections::class)->name('admin.attendance.corrections')->middleware('school.admin');
    Route::get('schools/{school}/admin/attendance/reports/daily', [AttendanceReportController::class, 'daily'])->name('admin.attendance.reports.daily')->middleware('school.admin');
    Route::get('schools/{school}/admin/attendance/reports/monthly', [AttendanceReportController::class, 'monthly'])->name('admin.attendance.reports.monthly')->middleware('school.admin');
    Route::get('schools/{school}/admin/attendance/reports/class', [AttendanceReportController::class, 'class'])->name('admin.attendance.reports.class')->middleware('school.admin');
    Route::get('schools/{school}/admin/students/{student}/attendance', [AttendanceReportController::class, 'student'])->name('admin.students.attendance')->middleware('school.admin');
});
