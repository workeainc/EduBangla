<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartExamAttempt
{
    public function handle(Exam $exam, int $schoolId): ExamAttempt
    {
        return DB::transaction(function () use ($exam, $schoolId) {
            $user = auth()->user();
            $student = Student::where(['school_id' => $schoolId, 'user_id' => $user?->id, 'status' => 'active'])->firstOrFail();
            if ($exam->school_id !== $schoolId || ! in_array($exam->status, ['scheduled', 'ongoing', 'published'], true)) {
                throw ValidationException::withMessages(['exam' => 'পরীক্ষাটি এখন শুরু করা যাবে না।']);
            }$enrollment = Enrollment::where(['school_id' => $schoolId, 'student_id' => $student->id, 'academic_year_id' => $exam->academic_year_id, 'status' => 'active'])->first();
            if (! $enrollment) {
                throw ValidationException::withMessages(['enrollment' => 'এই পরীক্ষার জন্য বৈধ enrollment নেই।']);
            }$schedule = $exam->schedules()->where(['school_id' => $schoolId, 'academic_year_id' => $enrollment->academic_year_id, 'class_id' => $enrollment->class_id, 'section_id' => $enrollment->section_id])->first();
            if (! $schedule) {
                throw ValidationException::withMessages(['exam' => 'এই শিক্ষার্থীর জন্য schedule নেই।']);
            }$start = Carbon::parse($schedule->scheduled_date->format('Y-m-d').' '.$schedule->start_time);
            $end = Carbon::parse($schedule->scheduled_date->format('Y-m-d').' '.$schedule->end_time);
            $now = now();
            if ($now->lt($start) || $now->gte($end)) {
                throw ValidationException::withMessages(['exam' => 'পরীক্ষার সময়সীমার বাইরে।']);
            }if (ExamAttempt::where(['exam_id' => $exam->id, 'student_id' => $student->id, 'status' => 'in_progress'])->exists()) {
                throw ValidationException::withMessages(['attempt' => 'একটি active attempt ইতোমধ্যে আছে।']);
            }$number = (ExamAttempt::where(['exam_id' => $exam->id, 'student_id' => $student->id])->max('attempt_number') ?? 0) + 1;
            $attempt = ExamAttempt::create(['school_id' => $schoolId, 'exam_id' => $exam->id, 'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'attempt_number' => $number, 'status' => 'in_progress', 'started_at' => $now, 'expires_at' => $end]);
            $paper = $schedule->examPaper()->with('questions.version.options')->first();
            if (! $paper) {
                throw ValidationException::withMessages(['exam' => 'প্রশ্নপত্র পাওয়া যায়নি।']);
            }foreach ($paper->questions as $pq) {
                $attempt->questions()->create(['school_id' => $schoolId, 'question_version_id' => $pq->question_version_id, 'question_type' => $pq->version->question->type, 'question_text' => $pq->version->prompt, 'marks' => $pq->marks, 'sort_order' => $pq->ordinal, 'options_snapshot' => $pq->version->options->map(fn ($o) => ['option_key' => $o->option_key, 'option_text' => $o->option_text])->values()->all()]);
            }if ($user) {
                app(RecordAudit::class)->handle($user, $schoolId, 'exam.attempt_started', $attempt, null, ['exam_id' => $exam->id, 'student_id' => $student->id]);
            }

return $attempt;
        });
    }
}
