<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->string('name', 120);
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'academic_year_id', 'class_id', 'section_id', 'name'], 'timetable_scope_name_unique');
            $t->index(['school_id', 'academic_year_id', 'class_id', 'section_id', 'status'], 'timetable_scope_status');
        });

        Schema::create('timetable_slots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('timetable_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('subject_assignment_id')->constrained()->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->foreignId('group_id')->nullable()->constrained('groups')->restrictOnDelete();
            $t->unsignedTinyInteger('weekday');
            $t->time('starts_at');
            $t->time('ends_at');
            $t->json('snapshot')->nullable();
            $t->timestamps();
            $t->unique(['timetable_id', 'weekday', 'starts_at', 'teacher_assignment_id'], 'timetable_slot_exact_unique');
            $t->index(['school_id', 'timetable_id', 'weekday', 'starts_at'], 'timetable_slot_schedule_index');
            $t->index(['school_id', 'teacher_id', 'weekday', 'starts_at'], 'timetable_slot_teacher_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('timetables');
    }
};
