<?php

namespace Tests\Feature;

use App\Domain\Access\Actions\ProvisionInitialSchoolOperator;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InitialSchoolOperatorProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisions_one_hashed_admin_and_is_idempotent_with_explicit_existing_flag(): void
    {
        $school = School::factory()->create();
        $r = app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'Pilot Admin', 'email' => 'admin@example.test', 'password' => 'secret-pass'], false);
        $this->assertTrue($r['created']);
        $this->assertSame('school-admin', $r['membership']->role);
        $this->assertTrue(Hash::check('secret-pass', $r['user']->password));
        $this->assertSame(1, SchoolUser::count());
        $again = app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'Ignored', 'email' => 'admin@example.test', 'password' => 'different-pass'], true);
        $this->assertTrue($again['resolved']);
        $this->assertSame(1, User::count());
        $this->assertSame('Pilot Admin', $again['user']->name);
        $this->assertFalse(Hash::check('different-pass', $again['user']->password));
    }

    public function test_rejects_invalid_school_email_conflicts_and_incompatible_memberships(): void
    {
        $school = School::factory()->create();
        $this->expectException(ValidationException::class);
        app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'x', 'email' => 'bad', 'password' => 'short']);
    }

    public function test_existing_email_conflict_and_other_school_membership_fail_closed(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $user = User::factory()->create(['email' => 'existing@example.test']);
        SchoolUser::create(['school_id' => $other->id, 'user_id' => $user->id, 'role' => 'school-admin', 'status' => 'active']);
        $this->expectException(ValidationException::class);
        app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'x', 'email' => 'existing@example.test', 'password' => 'secret-pass']);
    }

    public function test_incompatible_existing_membership_and_invalid_school_command_are_rejected(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['email' => 'inactive@example.test']);
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => 'teacher', 'status' => 'active']);
        $this->expectException(ValidationException::class);
        app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'x', 'email' => $user->email, 'password' => 'secret-pass'], true);
    }

    public function test_new_user_is_rolled_back_when_membership_step_fails(): void
    {
        $school = School::factory()->create();
        try {
            app(ProvisionInitialSchoolOperator::class)->handle($school, ['name' => 'Pilot Admin', 'email' => 'rollback@example.test', 'password' => 'secret-pass'], false, fn () => throw new \RuntimeException('forced failure'));
            $this->fail('Expected forced failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }
        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
        $this->assertDatabaseCount('school_users', 0);
    }

    public function test_artisan_command_supports_non_interactive_flow_without_printing_password(): void
    {
        $school = School::factory()->create(['name' => 'Command School']);
        $exit = Artisan::call('edubangla:provision-initial-operator', ['--school-id' => $school->id, '--name' => 'CLI Admin', '--email' => 'cli@example.test', '--password' => 'secret-pass', '--force' => true]);
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Initial school operator created', Artisan::output());
        $this->assertStringNotContainsString('secret-pass', Artisan::output());
        $this->assertDatabaseHas('school_users', ['school_id' => $school->id, 'role' => 'school-admin', 'status' => 'active']);
    }

    public function test_artisan_command_rejects_unknown_school(): void
    {
        $exit = Artisan::call('edubangla:provision-initial-operator', ['--school-id' => 999999, '--name' => 'x', '--email' => 'x@example.test', '--password' => 'secret-pass', '--force' => true]);
        $this->assertSame(2, $exit);
        $this->assertStringNotContainsString('secret-pass', Artisan::output());
    }
}
