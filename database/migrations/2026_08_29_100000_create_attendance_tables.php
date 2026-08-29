<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_assignment_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('period', 40)->default('regular');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'academic_year_id', 'class_id', 'section_id', 'teacher_assignment_id', 'attendance_date', 'period'], 'attendance_session_unique');
            $table->index(['school_id', 'attendance_date'], 'attendance_session_school_date');
        });
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'student_id'], 'student_attendance_unique');
            $table->index(['school_id', 'student_id'], 'student_attendance_school_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('attendance_sessions');
    }
};
