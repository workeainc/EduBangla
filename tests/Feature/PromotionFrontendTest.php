<?php

namespace Tests\Feature;

use App\Livewire\Admin\Promotions;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_promotion_workspace_renders_scoped_human_readable_selectors(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        SchoolUser::create(['school_id' => $school->id, 'user_id' => $admin->id, 'role' => 'school-admin', 'status' => 'active']);
        AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 Academic Year']);
        $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);

        Livewire::test(Promotions::class, ['school' => $school, 'promotion' => null])
            ->assertSee('Source enrollment')
            ->assertSee('Target academic year')
            ->assertSee('Target class')
            ->assertSee('No promotion candidates');
    }
}
