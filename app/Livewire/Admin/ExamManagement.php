<?php

namespace App\Livewire\Admin;

use App\Domain\Examination\Actions\CreateExam;
use App\Domain\Examination\Actions\TransitionExam;
use App\Domain\Examination\ExamStatus;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamType;
use App\Models\School;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ExamManagement extends Component
{
    public School $school;

    public array $form = [];

    public string $message = '';

    public function mount(School $school)
    {
        $this->school = $school;
        Gate::authorize('update', $school);
    }

    public function save()
    {
        $this->validate(['form.name' => 'required', 'form.code' => 'required', 'form.exam_type_id' => 'required', 'form.academic_year_id' => 'required']);
        (new CreateExam)->handle($this->form + ['school_id' => $this->school->id, 'created_by' => auth()->id()]);
        $this->message = 'পরীক্ষা তৈরি হয়েছে।';
        $this->form = [];
    }

    public function transition(int $id, string $to)
    {
        $exam = Exam::where('school_id', $this->school->id)->findOrFail($id);
        Gate::authorize('update', $exam);
        (new TransitionExam)->handle($exam, $to);
        $this->message = 'পরীক্ষার অবস্থা পরিবর্তন হয়েছে।';
    }

    public function render()
    {
        return view('livewire.admin.exam-management', ['years' => AcademicYear::where('school_id', $this->school->id)->get(), 'types' => ExamType::where('school_id', $this->school->id)->where('active', true)->get(), 'exams' => Exam::with('examType')->where('school_id', $this->school->id)->latest()->get(), 'statuses' => ExamStatus::cases()]);
    }
}
