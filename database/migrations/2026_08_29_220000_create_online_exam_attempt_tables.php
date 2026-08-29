<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->unique('student_user_unique')->after('school_id')->constrained('users')->nullOnDelete();
        });
        Schema::create('exam_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('attempt_number');
            $t->string('status', 20)->default('in_progress');
            $t->timestamp('started_at');
            $t->timestamp('expires_at');
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
            $t->unique(['exam_id', 'student_id', 'attempt_number'], 'exam_attempt_number_unique');
            $t->index(['school_id', 'student_id', 'status'], 'exam_attempt_scope');
        });
        Schema::create('exam_attempt_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_version_id')->nullable()->constrained()->nullOnDelete();
            $t->string('question_type', 20);
            $t->longText('question_text');
            $t->unsignedInteger('marks');
            $t->unsignedInteger('sort_order');
            $t->json('options_snapshot')->nullable();
            $t->timestamps();
            $t->unique(['exam_attempt_id', 'sort_order'], 'attempt_question_order');
        });
        Schema::create('exam_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exam_attempt_question_id')->constrained()->cascadeOnDelete();
            $t->json('answer_payload');
            $t->timestamp('answered_at');
            $t->timestamps();
            $t->unique('exam_attempt_question_id', 'exam_answer_question_unique');
            $t->index(['school_id', 'exam_attempt_id'], 'exam_answer_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempt_questions');
        Schema::dropIfExists('exam_attempts');
        Schema::table('students', fn (Blueprint $t) => $t->dropForeign(['user_id']));
        Schema::table('students', fn (Blueprint $t) => $t->dropUnique('student_user_unique'));
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn('user_id'));
    }
};
