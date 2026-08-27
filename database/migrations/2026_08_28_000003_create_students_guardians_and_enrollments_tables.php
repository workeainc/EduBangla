<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('student_code', 64);
            $t->string('first_name');
            $t->string('last_name')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('gender', 20)->nullable();
            $t->string('phone', 32)->nullable();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->date('admission_date')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'student_code']);
            $t->index(['school_id', 'status']);
        });
        Schema::create('guardians', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('phone', 32);
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->index(['school_id', 'phone']);
        });
        Schema::create('student_guardians', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('guardian_id')->constrained()->restrictOnDelete();
            $t->string('relationship_type', 32);
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
            $t->unique(['student_id', 'guardian_id']);
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->foreignId('group_id')->nullable()->constrained('groups')->restrictOnDelete();
            $t->unsignedBigInteger('group_scope')->default(0);
            $t->unsignedInteger('roll');
            $t->string('status', 20)->default('active');
            $t->date('enrolled_at');
            $t->timestamps();
            $t->unique(['school_id', 'academic_year_id', 'class_id', 'section_id', 'group_scope', 'roll'], 'enrollment_roll_scope_unique');
            $t->index(['school_id', 'student_id', 'academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('student_guardians');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
