<?php

namespace Tests\Feature;

use App\Domain\School\TenantContext;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_access_only_own_school(): void
    {
        $admin = User::factory()->create();
        $ownSchool = School::factory()->create();
        $otherSchool = School::factory()->create();
        $this->membership($admin, $ownSchool, 'school-admin');

        $this->actingAs($admin)->get(route('schools.show', $ownSchool))->assertOk()->assertJsonPath('id', $ownSchool->id);
        $this->actingAs($admin)->get(route('schools.show', $otherSchool))->assertForbidden();
    }

    public function test_user_without_membership_cannot_access_school_resource(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();

        $this->actingAs($user)->get(route('schools.show', $school))->assertForbidden();
    }

    public function test_super_admin_role_does_not_bypass_school_membership(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('Super Admin', 'web');
        $user->assignRole('Super Admin');
        $school = School::factory()->create();

        $this->actingAs($user)->get(route('schools.show', $school))->assertForbidden();
    }

    public function test_tenant_context_requires_active_membership_and_is_cleared(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $context = app(TenantContext::class);

        $this->expectException(AuthorizationException::class);
        $context->activate($school, $user);
    }

    public function test_tenant_context_can_be_established_and_cleared_for_member(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $this->membership($user, $school, 'teacher');
        $context = app(TenantContext::class);

        $context->activate($school, $user);
        $this->assertTrue($context->isActiveFor($school));
        $context->clear();
        $this->assertNull($context->school());
    }

    public function test_school_routes_must_use_explicit_tenant_context_middleware(): void
    {
        $route = Route::getRoutes()->getByName('schools.show');

        $this->assertContains('tenant.context', $route->gatherMiddleware());
    }

    private function membership(User $user, School $school, string $role): void
    {
        SchoolUser::query()->create(['user_id' => $user->id, 'school_id' => $school->id, 'role' => $role, 'status' => 'active']);
    }
}
