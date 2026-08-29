<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->decimal('minimum_percentage', 5, 2);
            $t->decimal('maximum_percentage', 5, 2);
            $t->string('letter_grade', 10);
            $t->decimal('grade_point', 4, 2);
            $t->boolean('is_pass')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['school_id', 'active', 'sort_order'], 'grade_rule_scope');
        });
        Schema::table('results', function (Blueprint $t) {
            $t->decimal('gpa', 4, 2)->nullable();
            $t->decimal('total_grade_points', 8, 2)->nullable();
            $t->unsignedInteger('graded_subject_count')->nullable();
            $t->string('overall_status', 20)->nullable();
        });
        Schema::table('result_items', function (Blueprint $t) {
            $t->string('letter_grade', 10)->nullable();
            $t->decimal('grade_point', 4, 2)->nullable();
            $t->boolean('is_pass')->nullable();
            $t->foreignId('grade_rule_id')->nullable()->constrained('grade_rules')->nullOnDelete();
        });
        Schema::create('report_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('result_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20)->default('draft');
            $t->decimal('gpa', 4, 2)->nullable();
            $t->string('overall_status', 20)->nullable();
            $t->json('snapshot')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'result_id'], 'report_result_unique');
            $t->index(['school_id', 'student_id', 'status'], 'report_student_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
        Schema::table('result_items', fn (Blueprint $t) => $t->dropForeign(['grade_rule_id']));
        Schema::table('result_items', fn (Blueprint $t) => $t->dropColumn(['letter_grade', 'grade_point', 'is_pass', 'grade_rule_id']));
        Schema::table('results', fn (Blueprint $t) => $t->dropColumn(['gpa', 'total_grade_points', 'graded_subject_count', 'overall_status']));
        Schema::dropIfExists('grade_rules');
    }
};
