<?php

namespace Tests\Feature;

use App\Domain\Access\Actions\ProvisionTeacherIdentity;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherIdentityProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_hashed_teacher_identity_membership_and_profile_link(): void
    {
        [$school, $teacher] = $this->teacher();
        $r = app(ProvisionTeacherIdentity::class)->handle($school, $teacher->id, ['name' => 'Teacher Account', 'email' => 'teacher@example.test', 'password' => 'secret-pass']);
        $this->assertTrue($r['created']);
        $this->assertTrue(Hash::check('secret-pass', $r['user']->password));
        $this->assertSame($r['user']->id, $teacher->fresh()->user_id);
        $this->assertDatabaseHas('school_users', ['school_id' => $school->id, 'user_id' => $r['user']->id, 'role' => 'teacher', 'status' => 'active']);
        $this->actingAs($r['user'])->withSession(['active_school_id' => $school->id])
            ->get(route('schools.dashboard', $school))->assertOk();
    }

    public function test_explicit_compatible_rerun_resolves_without_changes(): void
    {
        [$school, $teacher] = $this->teacher();
        $first = app(ProvisionTeacherIdentity::class)->handle($school, $teacher->id, ['name' => 'Teacher Account', 'email' => 'teacher@example.test', 'password' => 'secret-pass']);
        $again = app(ProvisionTeacherIdentity::class)->handle($school, $teacher->id, ['name' => 'Ignored', 'email' => 'teacher@example.test', 'password' => 'changed-pass'], true);
        $this->assertTrue($again['resolved']);
        $this->assertSame($first['user']->id, $again['user']->id);
        $this->assertSame(1, SchoolUser::count());
        $this->assertFalse(Hash::check('changed-pass', $again['user']->password));
    }

    public function test_rejects_foreign_profile_existing_email_and_incompatible_profile(): void
    {
        [$school] = $this->teacher();
        [, $foreignTeacher] = $this->teacher(School::factory()->create());
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(ProvisionTeacherIdentity::class)->handle($school, $foreignTeacher->id, ['name' => 'x', 'email' => 'x@example.test', 'password' => 'secret-pass']);
    }

    public function test_existing_email_in_another_school_and_rollback_fail_closed(): void
    {
        [$school, $teacher] = $this->teacher();
        $other = School::factory()->create();
        $user = User::factory()->create(['email' => 'existing@example.test']);
        SchoolUser::create(['school_id' => $other->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        try {
            app(ProvisionTeacherIdentity::class)->handle($school, $teacher->id, ['name' => 'x', 'email' => $user->email, 'password' => 'secret-pass'], true);
            $this->fail('Expected cross-school conflict.');
        } catch (ValidationException) {
        }
        try {
            app(ProvisionTeacherIdentity::class)->handle($school, $teacher->id, ['name' => 'Rollback', 'email' => 'rollback@example.test', 'password' => 'secret-pass'], false, fn () => throw new \RuntimeException('forced failure'));
            $this->fail('Expected forced failure.');
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
        $this->assertNull($teacher->fresh()->user_id);
    }

    public function test_command_creates_identity_without_disclosing_password(): void
    {
        [$school, $teacher] = $this->teacher();
        $exit = Artisan::call('edubangla:provision-teacher-identity', ['--school-id' => $school->id, '--teacher-id' => $teacher->id, '--name' => 'CLI Teacher', '--email' => 'cli@example.test', '--password' => 'secret-pass', '--force' => true]);
        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('secret-pass', Artisan::output());
        $this->assertNotNull($teacher->fresh()->user_id);
    }

    private function teacher(?School $school = null): array
    {
        $school ??= School::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'user_id' => null, 'status' => 'active']);

        return [$school, $teacher];
    }
}
