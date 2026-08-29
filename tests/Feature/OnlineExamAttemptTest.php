<?php

namespace Tests\Feature;

use App\Domain\Examination\Actions\SaveExamAnswer;
use App\Domain\Examination\Actions\StartExamAttempt;
use App\Domain\Examination\Actions\SubmitExamAttempt;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\ExamPaperQuestion;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnlineExamAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $type = ExamType::factory()->create(['school_id' => $school->id]);
        $exam = Exam::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'exam_type_id' => $type->id, 'status' => 'ongoing', 'created_by' => $user->id]);
        $sa = SubjectAssignment::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $ta = TeacherAssignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id]);
        $enrollment = Enrollment::create(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $date = now()->toDateString();
        $schedule = ExamSchedule::create(['school_id' => $school->id, 'exam_id' => $exam->id, 'academic_year_id' => $year->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'section_id' => $section->id, 'subject_assignment_id' => $sa->id, 'teacher_assignment_id' => $ta->id, 'teacher_id' => $teacher->id, 'scheduled_date' => $date, 'start_time' => now()->subMinute()->format('H:i:s'), 'end_time' => now()->addHour()->format('H:i:s'), 'maximum_marks' => 10, 'duration_minutes' => 60]);
        $bank = QuestionBank::create(['school_id' => $school->id, 'subject_id' => $subject->id, 'name' => 'Bank']);
        $question = Question::create(['school_id' => $school->id, 'question_bank_id' => $bank->id, 'stable_key' => 'ONLINE-1', 'type' => 'mcq']);
        $version = QuestionVersion::create(['school_id' => $school->id, 'question_id' => $question->id, 'version' => 1, 'prompt' => 'Original prompt', 'marks' => 10, 'created_by' => $user->id]);
        QuestionOption::create(['school_id' => $school->id, 'question_version_id' => $version->id, 'option_key' => 'A', 'option_text' => 'Alpha']);
        QuestionOption::create(['school_id' => $school->id, 'question_version_id' => $version->id, 'option_key' => 'B', 'option_text' => 'Beta']);
        $paper = ExamPaper::create(['school_id' => $school->id, 'exam_schedule_id' => $schedule->id, 'version' => 1, 'total_marks' => 10]);
        ExamPaperQuestion::create(['school_id' => $school->id, 'exam_paper_id' => $paper->id, 'question_version_id' => $version->id, 'ordinal' => 1, 'marks' => 10]);

        return compact('school', 'user', 'student', 'year', 'class', 'section', 'subject', 'exam', 'enrollment', 'schedule', 'version');
    }

    public function test_attempt_snapshots_paper_and_answers_are_server_owned(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['user']);
        $attempt = app(StartExamAttempt::class)->handle($f['exam'], $f['school']->id);
        $this->assertSame('Original prompt', $attempt->questions()->first()->question_text);
        $f['version']->update(['prompt' => 'Changed prompt']);
        $this->assertSame('Original prompt', $attempt->questions()->first()->fresh()->question_text);
        $q = $attempt->questions()->first();
        $answer = app(SaveExamAnswer::class)->handle($attempt, $q->id, 'A', $f['school']->id);
        $this->assertSame('A', $answer->answer_payload['value']);
        app(SubmitExamAttempt::class)->handle($attempt, $f['school']->id);
        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->expectException(ValidationException::class);
        app(SaveExamAnswer::class)->handle($attempt->fresh(), $q->id, 'B', $f['school']->id);
    }

    public function test_attempt_creation_rejects_duplicate_and_out_of_window(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['user']);
        $attempt = app(StartExamAttempt::class)->handle($f['exam'], $f['school']->id);
        $this->expectException(ValidationException::class);
        app(StartExamAttempt::class)->handle($f['exam'], $f['school']->id);
        $attempt->delete();
        $f['schedule']->update(['start_time' => now()->addHour()->format('H:i:s')]);
        $this->expectException(ValidationException::class);
        app(StartExamAttempt::class)->handle($f['exam'], $f['school']->id);
    }

    public function test_answer_rejects_foreign_attempt_and_expiry_without_write(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['user']);
        $attempt = app(StartExamAttempt::class)->handle($f['exam'], $f['school']->id);
        $question = $attempt->questions()->first();
        $attempt->update(['expires_at' => now()->subSecond()]);
        try {
            app(SaveExamAnswer::class)->handle($attempt->fresh(), $question->id, 'A', $f['school']->id);
            $this->fail('Expired answer was accepted.');
        } catch (ValidationException $e) {
            $this->assertSame(0, $attempt->answers()->count());
            $this->assertSame('in_progress', $attempt->fresh()->status);
        }
    }
}
