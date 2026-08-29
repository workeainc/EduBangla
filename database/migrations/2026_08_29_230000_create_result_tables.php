<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->string('status', 20)->default('draft');
            $t->unsignedInteger('total_obtained')->default(0);
            $t->unsignedInteger('total_marks')->default(0);
            $t->decimal('percentage', 5, 2)->default(0);
            $t->timestamp('computed_at')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['exam_id', 'student_id', 'enrollment_id'], 'result_exam_student_unique');
            $t->index(['school_id', 'status'], 'result_scope_status');
        });
        Schema::create('result_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('result_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->foreignId('exam_schedule_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('obtained_marks');
            $t->unsignedInteger('maximum_marks');
            $t->decimal('percentage', 5, 2);
            $t->string('source', 20)->default('manual');
            $t->timestamps();
            $t->unique(['result_id', 'subject_id'], 'result_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_items');
        Schema::dropIfExists('results');
    }
};
