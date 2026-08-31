<?php

namespace App\Livewire\Admin;

use App\Domain\Academic\Actions\ArchiveTimetable;
use App\Domain\Academic\Actions\PublishTimetable;
use App\Domain\Academic\Actions\SaveTimetableDraft;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\Timetable;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Timetables extends Component
{
    public School $school;

    public ?Timetable $timetable = null;

    public string $name = '';

    public $academic_year_id;

    public $class_id;

    public $section_id;

    public array $slots = [];

    public string $message = '';

    public function updatedAcademicYearId(): void { $this->class_id = $this->section_id = null; }
    public function updatedClassId(): void { $this->section_id = null; }

    public function addSlot(): void
    {
        $this->slots[] = ['teacher_assignment_id' => '', 'subject_assignment_id' => '', 'weekday' => 0, 'starts_at' => '09:00', 'ends_at' => '10:00'];
    }

    public function removeSlot(int $index): void
    {
        unset($this->slots[$index]);
        $this->slots = array_values($this->slots);
    }

    public function mount(School $school, ?Timetable $timetable = null): void
    {
        $this->school = $school;
        Gate::authorize('update', $school);
        if ($timetable && $timetable->school_id !== $school->id) {
            abort(404);
        }
        $this->timetable = $timetable;
        if ($timetable) {
            $this->name = $timetable->name;
            $this->academic_year_id = $timetable->academic_year_id;
            $this->class_id = $timetable->class_id;
            $this->section_id = $timetable->section_id;
            $this->slots = $timetable->slots->map(fn ($slot) => $slot->only(['teacher_assignment_id', 'subject_assignment_id', 'weekday', 'starts_at', 'ends_at']))->all();
        }
    }

    public function saveDraft(): void
    {
        $this->validate(['name' => 'required|string|max:120', 'academic_year_id' => 'required|integer', 'class_id' => 'required|integer', 'section_id' => 'required|integer', 'slots' => 'array', 'slots.*.teacher_assignment_id' => 'required|integer', 'slots.*.subject_assignment_id' => 'required|integer', 'slots.*.weekday' => 'required|integer', 'slots.*.starts_at' => 'required|string', 'slots.*.ends_at' => 'required|string']);
        $this->timetable = app(SaveTimetableDraft::class)->handle(auth()->user(), $this->school->id, ['name' => $this->name, 'academic_year_id' => $this->academic_year_id, 'class_id' => $this->class_id, 'section_id' => $this->section_id, 'slots' => $this->slots], $this->timetable?->id);
        $this->message = 'Timetable draft saved.';
    }

    public function publish(int $id): void
    {
        $this->timetable = app(PublishTimetable::class)->handle(auth()->user(), $this->school->id, $id);
        $this->message = 'Timetable published.';
    }

    public function archive(int $id): void
    {
        $this->timetable = app(ArchiveTimetable::class)->handle(auth()->user(), $this->school->id, $id);
        $this->message = 'Timetable archived.';
    }

    public function render()
    {
        return view('livewire.admin.timetables', ['timetables' => Timetable::forSchool($this->school)->withCount('slots')->latest()->get(), 'years' => AcademicYear::forSchool($this->school)->get(), 'classes' => AcademicClass::forSchool($this->school)->get(), 'sections' => Section::forSchool($this->school)->get(), 'assignments' => TeacherAssignment::query()->where('school_id', $this->school->id)->with(['teacher', 'subjectAssignment.subject', 'academicClass', 'section'])->get()]);
    }
}
