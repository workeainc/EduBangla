<?php

namespace Tests\Feature;

use App\Domain\Examination\Actions\EnterExamMark;
use App\Livewire\Teacher\ExamMarks;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_teacher_mark_entry_rejects_student_and_enrollment_substitution_and_lifecycle(): void
    {
        $s = School::factory()->create();
        $u = User::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $s->id, 'user_id' => $u->id]);
        $year = AcademicYear::factory()->create(['school_id' => $s->id]);
        $class = AcademicClass::factory()->create(['school_id' => $s->id]);
        $section = Section::factory()->create(['school_id' => $s->id, 'class_id' => $class->id]);
        $subject = Subject::factory()->create(['school_id' => $s->id]);
        $type = ExamType::factory()->create(['school_id' => $s->id]);
        $exam = Exam::factory()->create(['school_id' => $s->id, 'academic_year_id' => $year->id, 'exam_type_id' => $type->id, 'created_by' => $u->id]);
        $sa = SubjectAssignment::create(['school_id' => $s->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        $ta = TeacherAssignment::create(['school_id' => $s->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id]);
        $schedule = ExamSchedule::create(['school_id' => $s->id, 'exam_id' => $exam->id, 'academic_year_id' => $year->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id, 'teacher_assignment_id' => $ta->id, 'teacher_id' => $teacher->id, 'scheduled_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '10:00', 'maximum_marks' => 100, 'duration_minutes' => 60]);
        $student = Student::factory()->create(['school_id' => $s->id]);
        $other = Student::factory()->create(['school_id' => $s->id]);
        $enrollment = Enrollment::create(['school_id' => $s->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $this->actingAs($u);
        $before = ExamMark::count();
        foreach ([['student_id' => $other->id, 'enrollment_id' => $enrollment->id], ['student_id' => $student->id, 'enrollment_id' => $enrollment->id + 99999]] as $payload) {
            try {
                app(EnterExamMark::class)->handle($payload + ['school_id' => $s->id, 'exam_schedule_id' => $schedule->id, 'teacher_id' => $teacher->id, 'entered_by' => $u->id, 'marks' => 50, 'maximum_marks' => 100]);
                $this->fail('Substituted student/enrollment accepted.');
            } catch (ValidationException|ModelNotFoundException $e) {
                $this->assertTrue(true);
            }
        } $this->assertSame($before, ExamMark::count());
        $exam->update(['status' => 'locked']);
        $this->expectException(ValidationException::class);
        app(EnterExamMark::class)->handle(['school_id' => $s->id, 'exam_schedule_id' => $schedule->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'teacher_id' => $teacher->id, 'entered_by' => $u->id, 'marks' => 50, 'maximum_marks' => 100]);
    }
}
