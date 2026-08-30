<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationOperatorAccessTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_login_screen_is_available_and_registration_is_not_exposed(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in to your school');
        $this->assertFalse(Route::has('register'));
    }

    public function test_valid_login_regenerates_the_session_and_enters_a_single_active_school(): void
    {
        [$user, $school] = $this->operator('school-admin');
        $before = session()->getId();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('schools.dashboard', $school));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($school->id, session('active_school_id'));
        $this->assertNotSame($before, session()->getId());
    }

    public function test_invalid_credentials_do_not_authenticate_a_user(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->from(route('login'))->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        [$user] = $this->operator('school-admin');

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session('active_school_id'));
    }

    public function test_user_with_no_or_only_inactive_membership_can_log_in_but_cannot_enter_a_school(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'school-admin', 'status' => SchoolUser::STATUS_INACTIVE]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('schools.index'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('schools.index'))->assertOk()->assertSee('No active school access');
        $this->get(route('schools.dashboard', $school))->assertForbidden();
    }

    public function test_multiple_active_memberships_require_explicit_selection_and_reject_a_forged_school(): void
    {
        [$user, $first] = $this->operator('school-admin');
        $second = School::factory()->create();
        SchoolUser::create(['school_id' => $second->id, 'user_id' => $user->id, 'role' => 'staff', 'status' => SchoolUser::STATUS_ACTIVE]);
        $foreign = School::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('schools.index'));
        $this->post(route('schools.select'), ['school_id' => $foreign->id])->assertNotFound();
        $this->assertNull(session('active_school_id'));
        $this->post(route('schools.select'), ['school_id' => $first->id])->assertRedirect(route('schools.dashboard', $first));
        $this->assertSame($first->id, session('active_school_id'));
    }

    public function test_global_spatie_role_without_active_school_membership_does_not_authorize_school_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Teacher', 'web'));
        $school = School::factory()->create();

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertForbidden();
    }

    public function test_teacher_requires_a_matching_active_profile_before_dashboard_access(): void
    {
        [$user, $school] = $this->operator('teacher');

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertForbidden();

        Teacher::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertOk()->assertSee('My timetable');
    }

    public function test_student_requires_a_matching_active_profile_and_cannot_enter_a_foreign_school(): void
    {
        [$user, $school] = $this->operator('student');
        $foreign = School::factory()->create();

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertForbidden();
        Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertOk()->assertSee('Results');
        $this->actingAs($user)->get(route('schools.dashboard', $foreign))->assertForbidden();
    }

    public function test_parent_membership_is_explicitly_denied_from_the_operator_workspace(): void
    {
        [$user, $school] = $this->operator('parent');

        $this->actingAs($user)->get(route('schools.dashboard', $school))->assertForbidden();
    }

    /** @return array{User, School} */
    private function operator(string $role): array
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => $role, 'status' => SchoolUser::STATUS_ACTIVE]);

        return [$user, $school];
    }
}
