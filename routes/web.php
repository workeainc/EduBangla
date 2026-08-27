<?php

use App\Livewire\Admin\PhaseThreeManagement;
use App\Livewire\Teacher\MyAssignments;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/schools/{school}', function (School $school) {
        Gate::authorize('view', $school);

        return response()->json(['id' => $school->id, 'name' => $school->name]);
    })->name('schools.show');

    Route::prefix('schools/{school}/admin')->middleware('school.admin')->group(function () {
        Route::get('/teachers', PhaseThreeManagement::class)->defaults('screen', 'teachers')->name('admin.teachers');
        Route::get('/staff', PhaseThreeManagement::class)->defaults('screen', 'staff')->name('admin.staff');
        Route::get('/academic/class-groups', PhaseThreeManagement::class)->defaults('screen', 'class-groups')->name('admin.class-groups');
        Route::get('/academic/subject-assignments', PhaseThreeManagement::class)->defaults('screen', 'subject-assignments')->name('admin.subject-assignments');
        Route::get('/academic/teacher-assignments', PhaseThreeManagement::class)->defaults('screen', 'teacher-assignments')->name('admin.teacher-assignments');
    });
    Route::get('schools/{school}/teacher/assignments', MyAssignments::class)->name('teacher.assignments');
    Route::get('schools/{school}/teacher/profile', MyAssignments::class)->name('teacher.profile');
});
