<?php

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
});
