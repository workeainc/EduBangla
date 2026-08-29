<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 40);
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code'], 'exam_type_school_code');
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_type_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 40);
            $t->text('description')->nullable();
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('locked_at')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'academic_year_id', 'code'], 'exam_school_year_code');
            $t->index(['school_id', 'status'], 'exam_school_status');
        });
        Schema::create('exam_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->foreignId('group_id')->nullable()->constrained('groups')->restrictOnDelete();
            $t->foreignId('subject_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $t->date('scheduled_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->unsignedInteger('maximum_marks');
            $t->unsignedInteger('duration_minutes');
            $t->string('mode', 12)->default('offline');
            $t->timestamps();
            $t->unique(['school_id', 'exam_id', 'class_id', 'section_id', 'subject_assignment_id'], 'exam_schedule_scope_unique');
            $t->index(['school_id', 'scheduled_date'], 'exam_schedule_date');
        });
        Schema::create('question_banks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('language', 20)->default('bn');
            $t->string('curriculum_version', 40)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('question_bank_id')->constrained()->restrictOnDelete();
            $t->string('stable_key', 60);
            $t->string('type', 20);
            $t->string('topic')->nullable();
            $t->string('learning_objective')->nullable();
            $t->string('difficulty', 20)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'stable_key'], 'question_school_key');
        });
        Schema::create('question_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('question_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->longText('prompt');
            $t->unsignedInteger('marks')->default(1);
            $t->string('language', 20)->default('bn');
            $t->json('answer_config')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['question_id', 'version'], 'question_version_unique');
        });
        Schema::create('question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $t->string('option_key', 10);
            $t->text('option_text');
            $t->boolean('is_correct')->default(false);
            $t->timestamps();
            $t->unique(['question_version_id', 'option_key'], 'question_option_unique');
        });
        Schema::create('exam_papers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_schedule_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version')->default(1);
            $t->unsignedInteger('total_marks')->default(0);
            $t->timestamps();
            $t->unique(['exam_schedule_id', 'version'], 'exam_paper_version_unique');
        });
        Schema::create('exam_paper_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_paper_id')->constrained()->restrictOnDelete();
            $t->foreignId('question_version_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('ordinal');
            $t->unsignedInteger('marks');
            $t->timestamps();
            $t->unique(['exam_paper_id', 'ordinal'], 'exam_paper_question_ordinal');
        });
        Schema::create('exam_marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_schedule_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $t->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('marks');
            $t->unsignedInteger('maximum_marks');
            $t->timestamp('entered_at');
            $t->timestamps();
            $t->unique(['exam_schedule_id', 'student_id'], 'exam_mark_student_unique');
        });
    }

    public function down(): void
    {
        foreach (['exam_marks', 'exam_paper_questions', 'exam_papers', 'question_options', 'question_versions', 'questions', 'question_banks', 'exam_schedules', 'exams', 'exam_types'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
