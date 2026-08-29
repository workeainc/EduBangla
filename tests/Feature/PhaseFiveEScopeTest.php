<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Policies\PromotionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveEScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_student_promotion_routes_enforce_role_and_tenant(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $guest = $this->get(route('teacher.promotions', ['school' => $school]));
        $guest->assertRedirect(route('login'));
        $student = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $student->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $student->id, 'status' => 'active']);
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('admin.promotions', ['school' => $school]))->assertForbidden();
        $this->actingAs($student)->withSession(['active_school_id' => $school->id])->get(route('student.promotions', ['school' => $other]))->assertForbidden();
    }

    public function test_student_can_open_own_promotion_screen(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id])->get(route('student.promotions', ['school' => $school]))->assertOk();
    }

    public function test_student_cannot_view_another_students_promotion(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        $other = Student::factory()->create(['school_id' => $school->id]);
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'student', 'status' => 'active']);
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'status' => 'active']);
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2040']);
        $class = AcademicClass::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);
        $en = Enrollment::create(['school_id' => $school->id, 'student_id' => $other->id, 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $promotion = Promotion::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'student_id' => $other->id, 'source_enrollment_id' => $en->id, 'source_class_id' => $class->id, 'source_section_id' => $section->id, 'target_academic_year_id' => $year->id, 'target_class_id' => $class->id, 'status' => 'applied']);
        $this->assertFalse((new PromotionPolicy)->view($user, $promotion));
    }
}
