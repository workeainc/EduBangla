<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('source_class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('target_class_id')->constrained('classes')->restrictOnDelete();
            $t->string('minimum_overall_status', 20)->default('pass');
            $t->decimal('minimum_gpa', 4, 2)->nullable();
            $t->unsignedInteger('minimum_passed_subjects')->default(0);
            $t->unsignedInteger('failed_subject_tolerance')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['school_id', 'active'], 'promotion_rule_scope');
        });
        Schema::create('promotions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('source_enrollment_id')->constrained('enrollments')->restrictOnDelete();
            $t->foreignId('source_class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('source_section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $t->foreignId('target_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $t->foreignId('target_class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('target_section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $t->string('status', 20)->default('draft');
            $t->string('decision', 20)->nullable();
            $t->json('eligibility_basis')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->foreignId('target_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'student_id', 'academic_year_id'], 'promotion_student_year');
            $t->index(['school_id', 'status'], 'promotion_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('promotion_rules');
    }
};
