<?php

namespace Tests\Feature;

use App\Livewire\Teacher\ExamMarks;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExaminationTeacherScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_submit_marks_for_unassigned_schedule_or_foreign_school(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $u = User::factory()->create();
        SchoolUser::create(['school_id' => $a->id, 'user_id' => $u->id, 'role' => 'teacher', 'status' => 'active']);
        $ta = Teacher::factory()->create(['school_id' => $a->id, 'user_id' => $u->id]);
        $tb = Teacher::factory()->create(['school_id' => $b->id]);
        $ya = AcademicYear::factory()->create(['school_id' => $a->id]);
        $ca = AcademicClass::factory()->create(['school_id' => $a->id]);
        $sa = Section::factory()->create(['school_id' => $a->id, 'class_id' => $ca->id]);
        $sub = Subject::factory()->create(['school_id' => $a->id]);
        $exam = Exam::factory()->create(['school_id' => $a->id, 'academic_year_id' => $ya->id, 'exam_type_id' => ExamType::factory()->create(['school_id' => $a->id]), 'created_by' => $u->id]);
        $subjectAssignment = SubjectAssignment::create(['school_id' => $a->id, 'academic_year_id' => $ya->id, 'class_id' => $ca->id, 'subject_id' => $sub->id]);
        $teacherAssignment = TeacherAssignment::create(['school_id' => $a->id, 'teacher_id' => $ta->id, 'academic_year_id' => $ya->id, 'class_id' => $ca->id, 'section_id' => $sa->id, 'subject_assignment_id' => $subjectAssignment->id]);
        $schedule = ExamSchedule::create(['school_id' => $a->id, 'exam_id' => $exam->id, 'academic_year_id' => $ya->id, 'subject_id' => $sub->id, 'class_id' => $ca->id, 'section_id' => $sa->id, 'subject_assignment_id' => $subjectAssignment->id, 'teacher_assignment_id' => $teacherAssignment->id, 'teacher_id' => $tb->id, 'scheduled_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '10:00', 'maximum_marks' => 100, 'duration_minutes' => 60]);
        $this->actingAs($u)->withSession(['active_school_id' => $a->id]);
        $this->expectException(ModelNotFoundException::class);
        $component = app(ExamMarks::class);
        $component->school = $a;
        $component->exam = $exam;
        $component->save($schedule->id);
    }

    public function test_teacher_cannot_open_unassigned_exam(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $exam = Exam::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'exam_type_id' => ExamType::factory()->create(['school_id' => $school->id]), 'created_by' => $user->id]);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
        $this->expectException(HttpException::class);
        $component = app(ExamMarks::class);
        $component->mount($school, $exam);
    }
}
